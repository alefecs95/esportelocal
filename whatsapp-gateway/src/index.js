import { Boom } from '@hapi/boom'
import makeWASocket, {
    Browsers,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
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
    if (!digits.startsWith('55') || (digits.length !== 12 && digits.length !== 13)) {
        return [digits]
    }

    const ddd = digits.slice(2, 4)
    let thirteen = null
    let twelve = null

    if (digits.length === 13) {
        thirteen = digits
        const subscriber = digits.slice(4)
        if (subscriber.startsWith('9') && subscriber.length === 9) {
            twelve = `55${ddd}${subscriber.slice(1)}`
        }
    } else {
        twelve = digits
        const subscriber = digits.slice(4)
        thirteen = `55${ddd}9${subscriber}`
    }

    return [thirteen, twelve].filter(Boolean)
}

function waitForAck(session, messageId, timeoutMs = 15000) {
    if (!messageId) {
        return Promise.resolve(null)
    }

    return new Promise((resolve) => {
        const timer = setTimeout(() => {
            session.acks.delete(messageId)
            resolve(null)
        }, timeoutMs)

        session.acks.set(messageId, (status) => {
            clearTimeout(timer)
            resolve(status)
        })
    })
}

function asJid(value, fallbackDomain) {
    if (!value) {
        return null
    }
    const raw = String(value)
    if (raw.includes('@')) {
        return raw
    }
    return `${raw}@${fallbackDomain}`
}

async function resolveRecipient(sock, number) {
    const digits = normalizeDigits(number)
    if (!digits) {
        return null
    }

    const variants = brazilVariants(digits)
    for (const candidate of variants) {
        try {
            const result = await sock.onWhatsApp(candidate)
            const hits = Array.isArray(result) ? result : [result]
            const found = hits.find((item) => item && item.exists)
            if (!found) {
                continue
            }

            const pnJid = asJid(found.jid, 's.whatsapp.net')
            let lidJid = asJid(found.lid, 'lid')

            // Baileys 7: onWhatsApp não devolve LID — buscar no mapeamento/USync
            if (!lidJid && pnJid && sock.signalRepository?.lidMapping?.getLIDForPN) {
                try {
                    lidJid = await sock.signalRepository.lidMapping.getLIDForPN(pnJid)
                } catch (error) {
                    logger.warn({ err: error, pnJid }, 'Falha ao resolver LID a partir do PN')
                }
            }

            // Preferir LID (WhatsApp atual); PN só como fallback
            const jid = lidJid || pnJid
            if (jid) {
                logger.info({ candidate, jid, pnJid, lidJid, exists: found.exists }, 'JID resolvido')
                return { jid, pnJid, lidJid }
            }
        } catch (error) {
            logger.warn({ err: error, candidate }, 'Falha ao consultar JID no WhatsApp')
        }
    }

    return {
        jid: `${variants[0]}@s.whatsapp.net`,
        pnJid: `${variants[0]}@s.whatsapp.net`,
        lidJid: null,
    }
}

function sendTargets(recipient) {
    // Se há LID, envia só para LID (enviar PN com LID mapeado costuma falhar/silenciar)
    if (recipient.lidJid) {
        return [recipient.lidJid]
    }
    return [recipient.pnJid].filter(Boolean)
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
        acks: new Map(),
    }
    sessions.set(sessionId, session)

    const { state, saveCreds } = await useMultiFileAuthState(authPath(sessionId))
    const { version } = await fetchLatestBaileysVersion()
    const sock = makeWASocket({
        version,
        logger: pino({ level: process.env.BAILEYS_LOG_LEVEL || 'silent' }),
        printQRInTerminal: false,
        markOnlineOnConnect: true,
        syncFullHistory: false,
        browser: Browsers.ubuntu('Chrome'),
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
        },
        getMessage: async () => undefined,
    })

    session.sock = sock
    session.starting = false

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('messages.update', (updates) => {
        for (const item of updates) {
            const id = item?.key?.id
            const status = item?.update?.status
            const stubParams = item?.update?.messageStubParameters
            if (!id) {
                continue
            }

            if (status === undefined && !stubParams) {
                continue
            }

            logger.info({
                sessionId,
                id,
                status,
                jid: item?.key?.remoteJid,
                alt: item?.key?.remoteJidAlt,
                stubParams,
            }, 'Status da mensagem')

            const waiter = session.acks.get(id)
            if (!waiter) {
                continue
            }

            // ERROR = 0
            if (status === 0) {
                session.acks.delete(id)
                waiter(null)
                continue
            }

            // SERVER_ACK (2) ou superior = servidor aceitou
            if (typeof status === 'number' && status >= 2) {
                session.acks.delete(id)
                waiter(status)
            }
        }
    })

    sock.ev.on('message-receipt.update', (updates) => {
        for (const item of updates) {
            const id = item?.key?.id
            logger.info({
                sessionId,
                id,
                jid: item?.key?.remoteJid,
                receipt: item?.receipt,
            }, 'Recibo da mensagem')

            const waiter = id ? session.acks.get(id) : null
            if (waiter && (item?.receipt?.receiptTimestamp || item?.receipt?.readTimestamp)) {
                session.acks.delete(id)
                waiter(3)
            }
        }
    })

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
            current.phone = sock.user?.id?.split(':')[0] || sock.user?.id || null
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
        const recipient = await resolveRecipient(session.sock, number)
        if (!recipient?.jid) {
            return res.status(422).json({
                ok: false,
                message: 'Número de WhatsApp inválido.',
            })
        }

        const targets = sendTargets(recipient)
        let lastError = null

        for (const jid of targets) {
            try {
                logger.info({ sessionId, number, jid, lid: recipient.lidJid, pn: recipient.pnJid }, 'Enviando texto')
                const sent = await session.sock.sendMessage(jid, { text })
                const messageId = sent?.key?.id
                logger.info({
                    sessionId,
                    jid,
                    messageId,
                    remoteJid: sent?.key?.remoteJid,
                    remoteJidAlt: sent?.key?.remoteJidAlt,
                }, 'Texto entregue ao servidor')

                const status = await waitForAck(session, messageId)
                logger.info({ sessionId, jid, messageId, status }, 'Ack do WhatsApp')

                if (status === null) {
                    lastError = new Error('WhatsApp não confirmou o recebimento da mensagem.')
                    continue
                }

                return res.json({
                    ok: true,
                    jid,
                    messageId,
                    status,
                    lid: recipient.lidJid,
                    pn: recipient.pnJid,
                })
            } catch (error) {
                lastError = error
                logger.warn({ sessionId, jid, err: error }, 'Falha neste JID, tentando o próximo')
            }
        }

        throw lastError || new Error('Não foi possível enviar a mensagem.')
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
