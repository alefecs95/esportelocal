import { Boom } from '@hapi/boom'
import {
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    makeWASocket,
    useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import express from 'express'
import { existsSync, mkdirSync, readdirSync, rmSync } from 'fs'
import { join } from 'path'
import pino from 'pino'
import QRCode from 'qrcode'

const PORT = Number(process.env.PORT || 3000)
const AUTH_DIR = process.env.AUTH_DIR || join(process.cwd(), 'sessions')
const GATEWAY_TOKEN = String(process.env.GATEWAY_TOKEN || '').trim()
const logger = pino({ level: process.env.LOG_LEVEL || 'info' })

mkdirSync(AUTH_DIR, { recursive: true })

const sessions = new Map()

function publicState(session) {
    if (!session) {
        return {
            status: 'disconnected',
            phone: null,
            qr: null,
        }
    }

    return {
        status: session.status,
        phone: session.phone,
        qr: session.qr,
    }
}

function normalizeDigits(number) {
    let digits = String(number || '').replace(/\D+/g, '')
    if (!digits) {
        return ''
    }
    if (digits.length === 10 || digits.length === 11) {
        digits = `55${digits}`
    }
    return digits
}

function brazilVariants(digits) {
    const variants = new Set([digits])
    if (digits.startsWith('55') && digits.length === 13) {
        const ddd = digits.slice(2, 4)
        const subscriber = digits.slice(4)
        if (subscriber.startsWith('9') && subscriber.length === 9) {
            variants.add(`55${ddd}${subscriber.slice(1)}`)
        }
    }
    if (digits.startsWith('55') && digits.length === 12) {
        const ddd = digits.slice(2, 4)
        const subscriber = digits.slice(4)
        if (!subscriber.startsWith('9')) {
            variants.add(`55${ddd}9${subscriber}`)
        }
    }
    return [...variants]
}

async function resolveJid(sock, number) {
    const digits = normalizeDigits(number)
    if (!digits) {
        return null
    }

    const variants = brazilVariants(digits)
    for (const candidate of variants) {
        try {
            const result = await sock.onWhatsApp(candidate)
            const hits = Array.isArray(result) ? result : [result]
            const found = hits.find((item) => item?.exists && item?.jid)
            if (found?.jid) {
                return found.jid
            }
        } catch (error) {
            logger.warn({ err: error, candidate }, 'Falha ao consultar JID no WhatsApp')
        }
    }

    return `${variants[0]}@s.whatsapp.net`
}

function authPath(sessionId) {
    return join(AUTH_DIR, sessionId.replace(/[^a-zA-Z0-9_-]/g, '_'))
}

function wipeAuth(sessionId) {
    const dir = authPath(sessionId)
    if (existsSync(dir)) {
        rmSync(dir, { recursive: true, force: true })
    }
}

async function startSession(sessionId, { reset = false } = {}) {
    const existing = sessions.get(sessionId)

    if (reset && existing?.sock) {
        try {
            await existing.sock.logout()
        } catch {
            // ignore
        }
        try {
            existing.sock.end(undefined)
        } catch {
            // ignore
        }
        wipeAuth(sessionId)
        sessions.delete(sessionId)
    } else if (existing?.sock && ['connected', 'connecting', 'qr', 'reconnecting'].includes(existing.status)) {
        return existing
    }

    const session = {
        id: sessionId,
        sock: null,
        status: 'connecting',
        phone: null,
        qr: null,
        reconnectTimer: null,
        starting: true,
    }
    sessions.set(sessionId, session)

    const { state, saveCreds } = await useMultiFileAuthState(authPath(sessionId))
    const { version } = await fetchLatestBaileysVersion()
    const sock = makeWASocket({
        version,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false,
        markOnlineOnConnect: false,
        syncFullHistory: false,
        browser: ['SEMEL', 'Chrome', '120.0.0'],
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
        },
    })

    session.sock = sock
    session.starting = false

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', async (update) => {
        const current = sessions.get(sessionId)
        if (!current || current.sock !== sock) {
            return
        }

        const { connection, lastDisconnect, qr } = update

        if (qr) {
            current.status = 'qr'
            current.qr = await QRCode.toDataURL(qr)
            current.phone = null
            logger.info({ sessionId }, 'QR gerado para pareamento')
        }

        if (connection === 'open') {
            current.status = 'connected'
            current.qr = null
            current.phone = sock.user?.id?.split(':')[0] || null
            logger.info({ sessionId, phone: current.phone }, 'WhatsApp conectado')
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error instanceof Boom
                ? lastDisconnect.error.output.statusCode
                : lastDisconnect?.error?.output?.statusCode
            const loggedOut = statusCode === DisconnectReason.loggedOut

            current.qr = null
            current.phone = null

            try {
                sock.end(undefined)
            } catch {
                // ignore
            }

            if (loggedOut) {
                current.status = 'disconnected'
                current.sock = null
                wipeAuth(sessionId)
                logger.warn({ sessionId }, 'Sessão deslogada. Escaneie o QR novamente.')
                return
            }

            current.status = 'reconnecting'
            current.sock = null
            if (current.reconnectTimer) {
                clearTimeout(current.reconnectTimer)
            }
            current.reconnectTimer = setTimeout(() => {
                startSession(sessionId).catch((error) => {
                    logger.error({ sessionId, err: error }, 'Falha ao reconectar')
                })
            }, 4000)
            logger.warn({ sessionId, statusCode }, 'Conexão fechada. Reconectando...')
        }
    })

    return session
}

async function restoreSessions() {
    const dirs = readdirSync(AUTH_DIR, { withFileTypes: true })
        .filter((entry) => entry.isDirectory())
        .map((entry) => entry.name)

    for (const sessionId of dirs) {
        if (!existsSync(join(authPath(sessionId), 'creds.json'))) {
            continue
        }

        logger.info({ sessionId }, 'Restaurando sessão persistida')
        try {
            await startSession(sessionId)
        } catch (error) {
            logger.error({ sessionId, err: error }, 'Falha ao restaurar sessão')
        }
    }
}

function requireAuth(req, res, next) {
    if (!GATEWAY_TOKEN) {
        return next()
    }

    const header = String(req.headers.authorization || '')
    const bearer = header.startsWith('Bearer ') ? header.slice(7).trim() : ''
    const token = bearer || String(req.headers['x-gateway-token'] || '').trim()

    if (token !== GATEWAY_TOKEN) {
        return res.status(401).json({ ok: false, message: 'Token do gateway inválido.' })
    }

    return next()
}

const app = express()
app.use(express.json({ limit: '1mb' }))

app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'semel-whatsapp-gateway' })
})

app.use(requireAuth)

app.get('/sessions/:id', (req, res) => {
    res.json({
        ok: true,
        session: req.params.id,
        ...publicState(sessions.get(req.params.id)),
    })
})

app.post('/sessions/:id/connect', async (req, res) => {
    try {
        const session = await startSession(req.params.id, { reset: Boolean(req.body?.reset) })
        res.json({
            ok: true,
            session: req.params.id,
            ...publicState(session),
        })
    } catch (error) {
        logger.error({ err: error, sessionId: req.params.id }, 'Falha ao iniciar sessão')
        res.status(500).json({
            ok: false,
            message: 'Não foi possível iniciar a sessão WhatsApp.',
            error: error.message,
        })
    }
})

app.post('/sessions/:id/logout', async (req, res) => {
    const sessionId = req.params.id
    const session = sessions.get(sessionId)

    if (session?.reconnectTimer) {
        clearTimeout(session.reconnectTimer)
    }

    if (session?.sock) {
        try {
            await session.sock.logout()
        } catch {
            try {
                session.sock.end(undefined)
            } catch {
                // ignore
            }
        }
    }

    wipeAuth(sessionId)
    sessions.delete(sessionId)

    res.json({
        ok: true,
        session: sessionId,
        status: 'disconnected',
        phone: null,
        qr: null,
    })
})

app.post('/send/text', async (req, res) => {
    const sessionId = String(req.body?.session || '')
    const number = String(req.body?.number || '')
    const text = String(req.body?.text || '')

    if (!sessionId || !number || !text.trim()) {
        return res.status(422).json({
            ok: false,
            message: 'Informe session, number e text.',
        })
    }

    const session = sessions.get(sessionId)
    if (!session?.sock || session.status !== 'connected') {
        logger.warn({ sessionId, status: session?.status || 'disconnected', number }, 'Envio recusado: sessão desconectada')
        return res.status(409).json({
            ok: false,
            message: 'WhatsApp não está conectado para este tenant.',
            status: session?.status || 'disconnected',
        })
    }

    try {
        const jid = await resolveJid(session.sock, number)
        if (!jid) {
            return res.status(422).json({
                ok: false,
                message: 'Número de WhatsApp inválido.',
            })
        }

        logger.info({ sessionId, number, jid }, 'Enviando texto')
        await session.sock.sendMessage(jid, { text })
        logger.info({ sessionId, jid }, 'Texto enviado')
        return res.json({ ok: true, jid })
    } catch (error) {
        logger.error({ err: error, sessionId, number }, 'Falha ao enviar mensagem')
        return res.status(500).json({
            ok: false,
            message: 'Falha ao enviar mensagem WhatsApp.',
            error: error.message,
        })
    }
})

app.listen(PORT, '0.0.0.0', async () => {
    logger.info({ PORT, AUTH_DIR }, 'Gateway Baileys iniciado')
    await restoreSessions()
})
