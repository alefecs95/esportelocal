<?php

namespace Database\Seeders;

use App\Models\MemoriaAcervo;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaLocal;
use App\Models\MemoriaMarco;
use App\Models\MemoriaNotificacao;
use App\Models\MemoriaOpcao;
use App\Models\MemoriaPersonalidade;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemoriaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $prefeitura = Prefeitura::query()
            ->where('slug', 'padrao')
            ->orWhere('id', 1)
            ->first();

        if (!$prefeitura) {
            $this->command?->error('Prefeitura Padrão (slug padrao) não encontrada.');
            return;
        }

        App::instance('current_tenant', $prefeitura);

        $this->command?->info("Limpando Memória demo em {$prefeitura->nome}...");
        $this->wipe();

        $admin = $this->ensureUsers($prefeitura);
        $mods = $this->ensureModalidades();
        $this->ensureOpcoes();

        $locais = $this->seedLocais();
        $pessoas = $this->seedPersonalidades($mods, $admin);
        $marcos = $this->seedMarcos($mods, $locais, $pessoas, $admin);
        $this->seedAcervo($mods, $locais, $pessoas, $marcos, $admin);
        $this->seedAuditoriaNotificacoes($admin, $marcos, $pessoas);

        $this->command?->info('Memória demo pronta: locais, fatos, Hall, galeria, órfãs, envios pendentes e rascunhos.');
        $this->command?->info('Portal: /memoria  ·  Admin: /admin/memoria');
    }

    private function wipe(): void
    {
        MemoriaAcervo::query()->forceDelete();
        MemoriaMarco::query()->get()->each(function (MemoriaMarco $marco) {
            $marco->personalidades()->detach();
            $marco->forceDelete();
        });
        MemoriaPersonalidade::query()->forceDelete();
        MemoriaLocal::query()->forceDelete();
        MemoriaOpcao::query()->delete();
        MemoriaAuditoria::query()->delete();
        MemoriaNotificacao::query()->delete();
    }

    private function ensureUsers(Prefeitura $prefeitura): User
    {
        $admin = User::query()
            ->where('prefeitura_id', $prefeitura->id)
            ->whereIn('perfil', ['ADMIN', 'SUPER_ADMIN'])
            ->orderBy('id')
            ->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin Padrão',
                'email' => 'admin@padrao.local',
                'cpf' => '10000000001',
                'password' => Hash::make('password'),
                'perfil' => 'ADMIN',
                'status' => 'ATIVO',
                'prefeitura_id' => $prefeitura->id,
            ]);
        }

        User::firstOrCreate(
            ['email' => 'redator@padrao.local'],
            [
                'name' => 'Redator da Memória',
                'cpf' => '10000000002',
                'password' => Hash::make('password'),
                'perfil' => 'REDATOR',
                'status' => 'ATIVO',
                'prefeitura_id' => $prefeitura->id,
            ]
        );

        return $admin;
    }

    private function ensureModalidades(): array
    {
        $nomes = [
            'Futebol de Campo' => 'coletivo',
            'Futsal' => 'coletivo',
            'Voleibol' => 'coletivo',
            'Atletismo' => 'individual',
            'Natação' => 'individual',
        ];

        $out = [];
        foreach ($nomes as $nome => $tipo) {
            $out[$nome] = Modalidade::firstOrCreate(
                ['nome' => $nome],
                ['tipo' => $tipo, 'descricao' => $nome . ' na Memória do Esporte.', 'status' => true]
            );
        }

        return $out;
    }

    private function ensureOpcoes(): void
    {
        foreach ([
            ['grupo' => 'local_tipo', 'valor' => 'Campo'],
            ['grupo' => 'local_tipo', 'valor' => 'Piscina'],
            ['grupo' => 'marco_tipo', 'valor' => 'Festival'],
            ['grupo' => 'papel', 'valor' => 'Árbitro'],
        ] as $row) {
            MemoriaOpcao::firstOrCreate($row);
        }
    }

    private function seedLocais(): array
    {
        $rows = [
            ['nome' => 'Ginásio Municipal', 'tipo' => 'ginasio', 'endereco' => 'Centro, ao lado da prefeitura', 'seed' => 'ginasio'],
            ['nome' => 'Estádio da Vila', 'tipo' => 'estadio', 'endereco' => 'Vila Nova, rua do Estádio', 'seed' => 'estadio'],
            ['nome' => 'Quadra do Bairro Alto', 'tipo' => 'quadra', 'endereco' => 'Bairro Alto', 'seed' => 'quadra'],
            ['nome' => 'Pista Municipal de Atletismo', 'tipo' => 'pista', 'endereco' => 'Parque da Cidade', 'seed' => 'pista'],
            ['nome' => 'Campo da Várzea', 'tipo' => 'Campo', 'endereco' => 'Beira-rio', 'seed' => 'campo'],
            ['nome' => 'Piscina Municipal', 'tipo' => 'Piscina', 'endereco' => 'Complexo aquático', 'seed' => 'piscina'],
        ];

        $locais = [];
        foreach ($rows as $row) {
            $locais[$row['seed']] = MemoriaLocal::create([
                'nome' => $row['nome'],
                'tipo' => $row['tipo'],
                'endereco' => $row['endereco'],
                'descricao' => 'Espaço histórico da cidade usado em campeonatos e escolinhas.',
                'foto' => $this->img($row['seed'], 1400, 900),
                'publicado' => true,
            ]);
        }

        return $locais;
    }

    private function seedPersonalidades(array $mods, User $admin): array
    {
        $rows = [
            [
                'key' => 'maria',
                'nome' => 'Maria das Neves',
                'papel' => 'atleta',
                'mod' => 'Voleibol',
                'destaque' => true,
                'publicado' => true,
                'nasc' => '1968-03-12',
                'falec' => null,
                'bio' => 'Levantadora da geração de ouro do vôlei municipal. Começou na quadra do Bairro Alto e foi convocada para seletivas estaduais nos anos 80.',
            ],
            [
                'key' => 'joao',
                'nome' => 'João “Garrincha da Vila” Costa',
                'papel' => 'atleta',
                'mod' => 'Futebol de Campo',
                'destaque' => true,
                'publicado' => true,
                'nasc' => '1955-07-01',
                'falec' => null,
                'bio' => 'Ponta direita do Estádio da Vila. Marcou o gol do título de 1975 ainda lembrado na arquibancada.',
            ],
            [
                'key' => 'helena',
                'nome' => 'Helena Prado',
                'papel' => 'tecnico',
                'mod' => 'Futsal',
                'destaque' => true,
                'publicado' => true,
                'nasc' => '1972-11-20',
                'falec' => null,
                'bio' => 'Técnica das escolinhas municipais. Formou três gerações de atletas no Ginásio Municipal.',
            ],
            [
                'key' => 'carlos',
                'nome' => 'Carlos Eduardo Lima',
                'papel' => 'dirigente',
                'mod' => 'Futebol de Campo',
                'destaque' => false,
                'publicado' => true,
                'nasc' => '1948-02-02',
                'falec' => null,
                'bio' => 'Secretário de Esportes na inauguração do ginásio (1968) e na reforma da pista nos anos 90.',
            ],
            [
                'key' => 'rita',
                'nome' => 'Rita Nunes',
                'papel' => 'atleta',
                'mod' => 'Atletismo',
                'destaque' => false,
                'publicado' => true,
                'nasc' => '1981-05-09',
                'falec' => null,
                'bio' => 'Meio-fundista da Pista Municipal. Recorde local dos 800 m em 1999, ainda vigente nas categorias adultas.',
            ],
            [
                'key' => 'pedro',
                'nome' => 'Pedro Alencar',
                'papel' => 'Árbitro',
                'mod' => 'Futebol de Campo',
                'destaque' => false,
                'publicado' => true,
                'nasc' => '1960-09-18',
                'falec' => null,
                'bio' => 'Árbitro das finais municipais por duas décadas. Apitou a decisão de 1988 no Estádio da Vila.',
            ],
            [
                'key' => 'ana',
                'nome' => 'Ana Beatriz Souza',
                'papel' => 'atleta',
                'mod' => 'Natação',
                'destaque' => false,
                'publicado' => true,
                'nasc' => '1990-01-30',
                'falec' => null,
                'bio' => 'Nadadora da Piscina Municipal. Primeira atleta da cidade em uma seletiva nacional de 50 m livre.',
            ],
            [
                'key' => 'rascunho',
                'nome' => 'Perfil em curadoria',
                'papel' => 'atleta',
                'mod' => 'Futsal',
                'destaque' => false,
                'publicado' => false,
                'nasc' => null,
                'falec' => null,
                'bio' => 'Rascunho interno: biografia ainda em checagem pela curadoria.',
            ],
        ];

        $pessoas = [];
        foreach ($rows as $row) {
            $pessoas[$row['key']] = MemoriaPersonalidade::create([
                'nome' => $row['nome'],
                'papel' => $row['papel'],
                'mini_bio' => $row['bio'],
                'foto' => $this->img('pessoa-' . $row['key'], 800, 1000),
                'data_nascimento' => $row['nasc'],
                'data_falecimento' => $row['falec'],
                'modalidade_id' => $mods[$row['mod']]->id,
                'destaque' => $row['destaque'],
                'publicado' => $row['publicado'],
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        return $pessoas;
    }

    private function seedMarcos(array $mods, array $locais, array $pessoas, User $admin): array
    {
        $rows = [
            [
                'key' => 'ginasio68',
                'titulo' => 'Inauguração do Ginásio Municipal',
                'tipo' => 'inauguracao',
                'ano' => 1968,
                'data' => '1968-09-07',
                'mod' => 'Futsal',
                'local' => 'ginasio',
                'destaque' => true,
                'publicado' => true,
                'pessoas' => ['carlos'],
                'resumo' => 'A cidade ganhou o primeiro ginásio coberto. A festa lotou as arquibancadas de madeira.',
            ],
            [
                'key' => 'titulo75',
                'titulo' => 'Título invicto de 1975 no Estádio da Vila',
                'tipo' => 'conquista',
                'ano' => 1975,
                'data' => '1975-11-23',
                'mod' => 'Futebol de Campo',
                'local' => 'estadio',
                'destaque' => true,
                'publicado' => true,
                'pessoas' => ['joao', 'carlos'],
                'resumo' => 'O time da Vila fechou o campeonato sem derrotas. O gol de João Costa ainda é cantado nas arquibancadas.',
            ],
            [
                'key' => 'final88',
                'titulo' => 'Final municipal de 1988',
                'tipo' => 'campeonato',
                'ano' => 1988,
                'data' => '1988-12-04',
                'mod' => 'Futebol de Campo',
                'local' => 'estadio',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['pedro', 'joao'],
                'resumo' => 'Decisão apitada por Pedro Alencar, com o ginásio e o estádio transmitindo o jogo no alto-falante da praça.',
            ],
            [
                'key' => 'geracao94',
                'titulo' => 'Geração de ouro do vôlei',
                'tipo' => 'atleta',
                'ano' => 1994,
                'data' => '1994-06-18',
                'mod' => 'Voleibol',
                'local' => 'quadra',
                'destaque' => true,
                'publicado' => true,
                'pessoas' => ['maria', 'helena'],
                'resumo' => 'Maria das Neves liderou a campanha que colocou o vôlei feminino no mapa da cidade.',
            ],
            [
                'key' => 'pista96',
                'titulo' => 'Reforma da pista de atletismo',
                'tipo' => 'inauguracao',
                'ano' => 1996,
                'data' => '1996-04-21',
                'mod' => 'Atletismo',
                'local' => 'pista',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['carlos', 'rita'],
                'resumo' => 'Piso sintético novo e arquibancada lateral. Rita Nunes fez a primeira prova oficial no mesmo mês.',
            ],
            [
                'key' => 'recorde99',
                'titulo' => 'Recorde dos 800 metros',
                'tipo' => 'conquista',
                'ano' => 1999,
                'data' => '1999-08-14',
                'mod' => 'Atletismo',
                'local' => 'pista',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['rita'],
                'resumo' => 'Rita Nunes baixou a marca municipal e ainda detém o recorde adulto da cidade.',
            ],
            [
                'key' => 'festival02',
                'titulo' => 'Primeiro Festival das Escolinhas',
                'tipo' => 'Festival',
                'ano' => 2002,
                'data' => '2002-10-12',
                'mod' => 'Futsal',
                'local' => 'ginasio',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['helena'],
                'resumo' => 'Mais de 400 crianças no Ginásio Municipal. O festival virou calendário fixo da secretaria.',
            ],
            [
                'key' => 'natacao10',
                'titulo' => 'Piscina Municipal entra no calendário',
                'tipo' => 'inauguracao',
                'ano' => 2010,
                'data' => '2010-03-08',
                'mod' => 'Natação',
                'local' => 'piscina',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['ana'],
                'resumo' => 'A piscina semiolímpica passou a receber seletivas. Ana Beatriz foi a primeira medalha da casa.',
            ],
            [
                'key' => 'varzea18',
                'titulo' => 'Campeonato da Várzea 2018',
                'tipo' => 'campeonato',
                'ano' => 2018,
                'data' => '2018-09-30',
                'mod' => 'Futebol de Campo',
                'local' => 'campo',
                'destaque' => false,
                'publicado' => true,
                'pessoas' => ['pedro'],
                'resumo' => 'O campo de terra virou palco de 16 times de bairro. Final no fim de tarde, arquibancada improvisada.',
            ],
            [
                'key' => 'rascunho24',
                'titulo' => 'Marco em rascunho (ainda não no ar)',
                'tipo' => 'marco',
                'ano' => 2024,
                'data' => '2024-05-01',
                'mod' => 'Futsal',
                'local' => 'ginasio',
                'destaque' => false,
                'publicado' => false,
                'pessoas' => [],
                'resumo' => 'Texto interno: aguardando fotos e revisão da secretaria.',
            ],
        ];

        $marcos = [];
        foreach ($rows as $ordem => $row) {
            $conteudo = $row['resumo'] . "\n\nA curadoria reuniu recortes, fotos da arquibancada e depoimentos de quem estava lá. Este registro faz parte da Memória do Esporte da Prefeitura Padrão.";
            $marco = MemoriaMarco::create([
                'titulo' => $row['titulo'],
                'resumo' => $row['resumo'],
                'conteudo' => $conteudo,
                'data_evento' => $row['data'],
                'ano' => $row['ano'],
                'tipo' => $row['tipo'],
                'modalidade_id' => $mods[$row['mod']]->id,
                'local_id' => $locais[$row['local']]->id,
                'capa' => $this->img('marco-' . $row['key'], 1600, 900),
                'destaque' => $row['destaque'],
                'publicado' => $row['publicado'],
                'ordem' => $ordem,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $ids = [];
            foreach ($row['pessoas'] as $key) {
                $ids[] = $pessoas[$key]->id;
            }
            $marco->personalidades()->sync($ids);
            $marcos[$row['key']] = $marco;
        }

        return $marcos;
    }

    private function seedAcervo(array $mods, array $locais, array $pessoas, array $marcos, User $admin): void
    {
        $galeria = [
            ['Título 1975 — comemoração no gramado', 1975, 'estadio', 'Futebol de Campo', 'titulo75', 'joao', 'arquivo_publico'],
            ['Arquibancada de madeira do ginásio, anos 70', 1972, 'ginasio', 'Futsal', 'ginasio68', null, 'jornal'],
            ['João Costa em ação na ponta direita', 1975, 'estadio', 'Futebol de Campo', 'titulo75', 'joao', 'arquivo_publico'],
            ['Time campeão posado no Estádio da Vila', 1975, 'estadio', 'Futebol de Campo', 'titulo75', 'joao', 'secretaria'],
            ['Placa da inauguração do ginásio', 1968, 'ginasio', 'Futsal', 'ginasio68', 'carlos', 'secretaria'],
            ['Festa na praça no dia da inauguração', 1968, 'ginasio', 'Futsal', 'ginasio68', 'carlos', 'jornal'],
            ['Final de 1988 — bola na grande área', 1988, 'estadio', 'Futebol de Campo', 'final88', 'pedro', 'jornal'],
            ['Pedro Alencar apitando a decisão', 1988, 'estadio', 'Futebol de Campo', 'final88', 'pedro', 'clube'],
            ['Maria das Neves no saque, 1994', 1994, 'quadra', 'Voleibol', 'geracao94', 'maria', 'arquivo_publico'],
            ['Equipe feminina de vôlei no Bairro Alto', 1994, 'quadra', 'Voleibol', 'geracao94', 'maria', 'clube'],
            ['Helena Prado no banco das escolinhas', 1994, 'ginasio', 'Futsal', 'geracao94', 'helena', 'secretaria'],
            ['Pista recém-reformada em 1996', 1996, 'pista', 'Atletismo', 'pista96', 'rita', 'secretaria'],
            ['Rita Nunes na chegada dos 800 m', 1999, 'pista', 'Atletismo', 'recorde99', 'rita', 'jornal'],
            ['Pódio municipal de atletismo', 1999, 'pista', 'Atletismo', 'recorde99', 'rita', 'arquivo_publico'],
            ['Crianças no Festival das Escolinhas', 2002, 'ginasio', 'Futsal', 'festival02', 'helena', 'secretaria'],
            ['Fila para a inscrição do festival', 2002, 'ginasio', 'Futsal', 'festival02', 'helena', 'secretaria'],
            ['Faixa de abertura do festival', 2002, 'ginasio', 'Futsal', 'festival02', null, 'outro'],
            ['Piscina lotada na seletiva de 2010', 2010, 'piscina', 'Natação', 'natacao10', 'ana', 'secretaria'],
            ['Ana Beatriz na raia 4', 2010, 'piscina', 'Natação', 'natacao10', 'ana', 'clube'],
            ['Torcida na beira da piscina', 2010, 'piscina', 'Natação', 'natacao10', null, 'jornal'],
            ['Campo da Várzea no fim de tarde', 2018, 'campo', 'Futebol de Campo', 'varzea18', null, 'outro'],
            ['Final da várzea — times mistos', 2018, 'campo', 'Futebol de Campo', 'varzea18', 'pedro', 'clube'],
            ['Bandeira de canto no campo de terra', 2018, 'campo', 'Futebol de Campo', 'varzea18', null, 'jornal'],
            ['Quadra do Bairro Alto à noite', 1993, 'quadra', 'Voleibol', 'geracao94', null, 'arquivo_publico'],
            ['Vestiário antigo do ginásio', 1982, 'ginasio', 'Futsal', null, null, 'arquivo_publico'],
            ['Medalhas da secretaria, acervo interno', 2005, 'ginasio', 'Futsal', null, 'helena', 'secretaria'],
            ['Cartaz do campeonato de 1988', 1988, 'estadio', 'Futebol de Campo', 'final88', null, 'jornal'],
            ['Escolinha de futsal no intervalo', 2004, 'ginasio', 'Futsal', 'festival02', 'helena', 'secretaria'],
            ['Corrida na pista ao amanhecer', 2001, 'pista', 'Atletismo', 'recorde99', 'rita', 'arquivo_publico'],
            ['Arquibancada do Estádio da Vila', 1980, 'estadio', 'Futebol de Campo', 'titulo75', null, 'jornal'],
        ];

        foreach ($galeria as $i => $item) {
            $this->foto([
                'titulo' => $item[0],
                'ano' => $item[1],
                'local' => $locais[$item[2]]->id,
                'mod' => $mods[$item[3]]->id,
                'marco' => $item[4] ? $marcos[$item[4]]->id : null,
                'pessoa' => $item[5] ? $pessoas[$item[5]]->id : null,
                'fonte_tipo' => $item[6],
                'seed' => 'galeria-' . $i,
                'alt' => $i % 11 === 0 ? null : $item[0],
                'publicado' => true,
                'status' => 'aprovado',
                'origem' => 'curadoria',
            ]);
        }

        for ($i = 0; $i < 18; $i++) {
            $this->foto([
                'titulo' => 'Foto avulsa da galeria #' . ($i + 1),
                'ano' => 1968 + ($i * 3),
                'local' => $locais[array_keys($locais)[$i % count($locais)]]->id,
                'mod' => $mods['Futebol de Campo']->id,
                'marco' => null,
                'pessoa' => null,
                'fonte_tipo' => 'arquivo_publico',
                'seed' => 'orfa-' . $i,
                'alt' => 'Registro histórico sem vínculo a fato ou pessoa, item ' . ($i + 1),
                'publicado' => true,
                'status' => 'aprovado',
                'origem' => 'curadoria',
            ]);
        }

        foreach (['titulo75', 'festival02', 'natacao10'] as $i => $key) {
            MemoriaAcervo::create([
                'tipo' => 'video',
                'titulo' => 'Vídeo de arquivo — ' . $marcos[$key]->titulo,
                'alt_text' => 'Vídeo histórico de ' . $marcos[$key]->titulo,
                'descricao' => 'Trecho digitalizado do acervo da secretaria.',
                'historia' => 'Fita VHS recuperada no arquivo municipal.',
                'arquivo' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
                'mime' => 'video/mp4',
                'ano' => $marcos[$key]->ano,
                'local_id' => $marcos[$key]->local_id,
                'modalidade_id' => $marcos[$key]->modalidade_id,
                'marco_id' => $marcos[$key]->id,
                'fonte' => 'Arquivo da Secretaria de Esportes',
                'fonte_tipo' => 'secretaria',
                'origem' => 'curadoria',
                'status' => 'aprovado',
                'publicado' => true,
            ]);
        }

        foreach (['ginasio68', 'final88'] as $key) {
            MemoriaAcervo::create([
                'tipo' => 'documento',
                'titulo' => 'Recorte / ata — ' . $marcos[$key]->titulo,
                'alt_text' => 'Documento digitalizado',
                'descricao' => 'PDF de recorte de jornal ou ata da inauguração.',
                'arquivo' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'mime' => 'application/pdf',
                'ano' => $marcos[$key]->ano,
                'marco_id' => $marcos[$key]->id,
                'fonte' => 'Jornal da Cidade',
                'fonte_tipo' => 'jornal',
                'origem' => 'curadoria',
                'status' => 'aprovado',
                'publicado' => true,
            ]);
        }

        $pendentes = [
            ['Foto do time de várzea do meu avô', 'José Almeida', '(15) 98888-1001', 1978, 'campo'],
            ['Recorte da final de 1988 que estava no álbum', 'Lúcia Ferreira', '(15) 98888-1002', 1988, 'estadio'],
            ['Crianças no festival de 2002', 'Marcos Vieira', '(15) 98888-1003', 2002, 'ginasio'],
            ['Natação na piscina nova', 'Patrícia Dias', '(15) 98888-1004', 2010, 'piscina'],
            ['Pista lotada num sábado de treino', 'Roberto Lima', '(15) 98888-1005', 1997, 'pista'],
            ['Quadra do Bairro Alto depois da pintura', 'Sandra Gomes', '(15) 98888-1006', 1994, 'quadra'],
        ];

        foreach ($pendentes as $i => $p) {
            MemoriaAcervo::create([
                'tipo' => 'foto',
                'titulo' => $p[0],
                'alt_text' => $p[0],
                'descricao' => 'Envio da comunidade para curadoria.',
                'historia' => 'Guardei este arquivo em casa. Acho que ajuda a contar a história do esporte na cidade.',
                'arquivo' => $this->img('pendente-' . $i, 1200, 800),
                'mime' => 'image/jpeg',
                'ano' => $p[3],
                'local_id' => $locais[$p[4]]->id,
                'doacao' => 'Álbum de família',
                'origem' => 'comunidade',
                'doador_nome' => $p[1],
                'doador_telefone' => $p[2],
                'status' => 'pendente',
                'publicado' => false,
            ]);
        }

        MemoriaAcervo::create([
            'tipo' => 'foto',
            'titulo' => 'Foto recusada (baixa qualidade)',
            'descricao' => 'Arquivo ilegível.',
            'arquivo' => $this->img('rejeitada', 400, 300),
            'mime' => 'image/jpeg',
            'ano' => 2001,
            'origem' => 'comunidade',
            'doador_nome' => 'Envio anônimo',
            'doador_telefone' => '(15) 98888-1099',
            'status' => 'rejeitado',
            'motivo_rejeicao' => 'Imagem ilegível e sem contexto suficiente para publicar.',
            'publicado' => false,
            'revisado_por' => $admin->id,
            'revisado_em' => now(),
        ]);
    }

    private function seedAuditoriaNotificacoes(User $admin, array $marcos, array $pessoas): void
    {
        foreach ([
            ['criou', 'marco', $marcos['ginasio68']->id, $marcos['ginasio68']->titulo],
            ['publicou', 'marco', $marcos['titulo75']->id, $marcos['titulo75']->titulo],
            ['editou', 'personalidade', $pessoas['maria']->id, $pessoas['maria']->nome],
            ['aprovou', 'acervo', null, 'Lote inicial do acervo demo'],
        ] as $log) {
            MemoriaAuditoria::create([
                'user_id' => $admin->id,
                'acao' => $log[0],
                'entidade_tipo' => $log[1],
                'entidade_id' => $log[2],
                'resumo' => $log[3],
            ]);
        }

        $curadores = User::query()
            ->whereIn('perfil', ['ADMIN', 'SUPER_ADMIN', 'REDATOR'])
            ->where('status', 'ATIVO')
            ->get();

        foreach ($curadores as $user) {
            MemoriaNotificacao::create([
                'user_id' => $user->id,
                'tipo' => 'envio',
                'titulo' => 'Novo envio da comunidade',
                'mensagem' => 'José Almeida enviou “Foto do time de várzea do meu avô”.',
                'link' => '/admin/memoria',
                'lida' => false,
            ]);
        }
    }

    private function foto(array $d): MemoriaAcervo
    {
        return MemoriaAcervo::create([
            'tipo' => 'foto',
            'titulo' => $d['titulo'],
            'alt_text' => $d['alt'],
            'descricao' => $d['titulo'],
            'historia' => 'Item do acervo institucional da Prefeitura Padrão, digitalizado para a Memória do Esporte.',
            'arquivo' => $this->img($d['seed'], 1200, 800),
            'mime' => 'image/jpeg',
            'ano' => $d['ano'],
            'local_id' => $d['local'],
            'modalidade_id' => $d['mod'],
            'marco_id' => $d['marco'],
            'personalidade_id' => $d['pessoa'],
            'pessoas_envolvidas' => $d['pessoa'] ? null : 'Arquivo municipal',
            'fonte' => 'Arquivo da Secretaria de Esportes',
            'fonte_tipo' => $d['fonte_tipo'],
            'origem' => $d['origem'],
            'status' => $d['status'],
            'publicado' => $d['publicado'],
        ]);
    }

    private function img(string $seed, int $w, int $h): string
    {
        return 'https://picsum.photos/seed/' . Str::slug($seed) . '/' . $w . '/' . $h;
    }
}
