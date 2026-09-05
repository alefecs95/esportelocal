<?php

namespace Database\Seeders;

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaCorrecao;
use App\Models\MemoriaEquipe;
use App\Models\MemoriaIdentificacao;
use App\Models\MemoriaLocal;
use App\Models\MemoriaLocalMomento;
use App\Models\MemoriaMarco;
use App\Models\MemoriaNotificacao;
use App\Models\MemoriaOpcao;
use App\Models\MemoriaPersonalidade;
use App\Models\MemoriaRecorde;
use App\Models\MemoriaTag;
use App\Models\MemoriaVotoHall;
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

        $stamp = 'catalogo-completo-2026-09-05';
        if (filter_var(env('SEED_MEMORIA_IF_EMPTY', false), FILTER_VALIDATE_BOOLEAN)
            && MemoriaOpcao::query()->where('grupo', 'demo_memoria')->where('valor', $stamp)->exists()) {
            $this->command?->info('Demo da Memória no Padrão já está na versão completa — pulando.');
            return;
        }

        $this->command?->info("Limpando Memória demo em {$prefeitura->nome}...");
        $this->wipe();

        $admin = $this->ensureUsers($prefeitura);
        $mods = $this->ensureModalidades();
        $this->ensureOpcoes();

        $locais = $this->seedLocais();
        $pessoas = $this->seedPersonalidades($mods, $admin);
        $marcos = $this->seedMarcos($mods, $locais, $pessoas, $admin);
        $equipes = $this->seedEquipes($mods, $pessoas, $marcos);
        $this->seedAcervo($mods, $locais, $pessoas, $marcos, $admin);
        $this->catalogarCampos($mods, $locais, $pessoas, $marcos, $equipes, $admin);
        $this->seedRecordes($mods, $pessoas, $marcos, $equipes);
        $this->seedAuditoriaNotificacoes($admin, $marcos, $pessoas);
        MemoriaOpcao::firstOrCreate(['grupo' => 'demo_memoria', 'valor' => $stamp]);

        $this->command?->info('Demo da Memória no Padrão: fatos, Hall, equipes, tags, correções, momentos, transcrições, recordes e fila de curadoria.');
        $this->command?->info('Portal: /memoria  ·  Admin: /admin/memoria  ·  redator@padrao.local / password');
    }

    private function wipe(): void
    {
        MemoriaIdentificacao::query()->delete();
        MemoriaCorrecao::query()->delete();
        MemoriaVotoHall::query()->delete();
        MemoriaLocalMomento::query()->delete();
        MemoriaRecorde::query()->forceDelete();
        MemoriaAcervo::query()->get()->each(function (MemoriaAcervo $item) {
            $item->tags()->detach();
            $item->equipes()->detach();
            $item->marcos()->detach();
            $item->pessoas()->detach();
            $item->forceDelete();
        });
        MemoriaEquipe::query()->get()->each(function (MemoriaEquipe $equipe) {
            $equipe->pessoas()->detach();
            $equipe->marcos()->detach();
            $equipe->forceDelete();
        });
        MemoriaTag::query()->delete();
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
            ['nome' => 'Ginásio Municipal', 'tipo' => 'ginasio', 'endereco' => 'Centro, ao lado da prefeitura', 'seed' => 'ginasio', 'lat' => -6.62510, 'lng' => -42.14520],
            ['nome' => 'Estádio da Vila', 'tipo' => 'estadio', 'endereco' => 'Vila Nova, rua do Estádio', 'seed' => 'estadio', 'lat' => -6.62840, 'lng' => -42.15180],
            ['nome' => 'Quadra do Bairro Alto', 'tipo' => 'quadra', 'endereco' => 'Bairro Alto', 'seed' => 'quadra', 'lat' => -6.61980, 'lng' => -42.13950],
            ['nome' => 'Pista Municipal de Atletismo', 'tipo' => 'pista', 'endereco' => 'Parque da Cidade', 'seed' => 'pista', 'lat' => -6.63120, 'lng' => -42.14210],
            ['nome' => 'Campo da Várzea', 'tipo' => 'Campo', 'endereco' => 'Beira-rio', 'seed' => 'campo', 'lat' => -6.63400, 'lng' => -42.15640],
            ['nome' => 'Piscina Municipal', 'tipo' => 'Piscina', 'endereco' => 'Complexo aquático', 'seed' => 'piscina', 'lat' => -6.62250, 'lng' => -42.14870],
        ];

        $textos = [
            'ginasio' => 'Primeiro ginásio coberto da cidade. Arquibancada de madeira na inauguração; piso de taco nas escolinhas; palco do Festival desde 2002.',
            'estadio' => 'Estádio da Vila, palco do título invicto de 1975 e da final de 1988. Gramado e arquibancada que a torcida do Comercial chama de casa.',
            'quadra' => 'Quadra do Bairro Alto, berço da geração de ouro do vôlei feminino. Iluminação noturna a partir dos anos 90.',
            'pista' => 'Pista municipal de atletismo no Parque da Cidade. Reforma de 1996 e recorde dos 800 m em 1999.',
            'campo' => 'Campo da Várzea na beira-rio. Terra batida, campeonato de bairro e final ao entardecer.',
            'piscina' => 'Piscina semiolímpica do complexo aquático. Entrou no calendário em 2010 com a seletiva de Ana Beatriz.',
        ];
        $locais = [];
        foreach ($rows as $row) {
            $locais[$row['seed']] = MemoriaLocal::create([
                'nome' => $row['nome'],
                'tipo' => $row['tipo'],
                'endereco' => $row['endereco'],
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'descricao' => $textos[$row['seed']],
                'foto' => $this->img($row['seed'], 1400, 900),
                'foto_antes' => $this->img($row['seed'] . '-antes', 1400, 900),
                'foto_depois' => $this->img($row['seed'] . '-depois', 1400, 900),
                'ano_antes' => 1985,
                'ano_depois' => 2024,
                'publicado' => true,
            ]);
        }

        foreach ($locais as $local) {
            MemoriaLocalMomento::create([
                'local_id' => $local->id,
                'ano' => 1985,
                'titulo' => 'Antes da reforma',
                'foto' => $local->foto_antes,
                'ordem' => 1,
            ]);
            MemoriaLocalMomento::create([
                'local_id' => $local->id,
                'ano' => 2024,
                'titulo' => 'Depois da reforma',
                'foto' => $local->foto_depois,
                'ordem' => 2,
            ]);
        }

        MemoriaLocalMomento::create([
            'local_id' => $locais['ginasio']->id,
            'ano' => 1968,
            'titulo' => 'Inauguração',
            'descricao' => 'O ginásio coberto no dia da festa.',
            'foto' => $this->img('ginasio-1968', 1400, 900),
            'ordem' => 0,
        ]);

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
                'apelido' => 'Neves',
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
                'apelido' => 'Tia Helena',
            ],
            [
                'key' => 'carlos',
                'nome' => 'Carlos Eduardo Lima',
                'papel' => 'dirigente',
                'mod' => 'Futebol de Campo',
                'destaque' => false,
                'publicado' => true,
                'nasc' => '1948-02-02',
                'falec' => '2019-06-11',
                'bio' => 'Secretário de Esportes na inauguração do ginásio (1968) e na reforma da pista nos anos 90. Falecido em 2019; o Hall mantém o perfil em memória.',
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
                'apelido' => $row['apelido'] ?? ($row['key'] === 'joao' ? 'Garrincha da Vila' : null),
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

    private function seedEquipes(array $mods, array $pessoas, array $marcos): array
    {
        $comercial = MemoriaEquipe::create([
            'nome' => 'Comercial Futebol Clube',
            'apelido' => 'Leão da Vila',
            'nome_anterior' => 'Esporte Clube Vila Nova',
            'escudo' => $this->img('escudo-comercial', 400, 400),
            'modalidade_id' => $mods['Futebol de Campo']->id,
            'ano_inicio' => 1952,
            'ano_fim' => null,
            'cidade' => 'Vila Nova',
            'descricao' => 'O time da Vila que levou a cidade ao título invicto de 1975.',
            'historia' => 'Surgiu nos campos de terra da Vila Nova. Camisa grená, torcida de bairro e rivalidade boa com o time do Centro. Ainda joga a várzea municipal.',
            'publicado' => true,
        ]);
        $comercial->pessoas()->sync([
            $pessoas['joao']->id => ['papel' => 'atleta', 'ano_inicio' => 1970, 'ano_fim' => 1982],
            $pessoas['carlos']->id => ['papel' => 'dirigente', 'ano_inicio' => 1968, 'ano_fim' => 1985],
        ]);
        $comercial->marcos()->sync(array_filter([
            $marcos['titulo75']->id ?? null,
            $marcos['final88']->id ?? null,
        ]));

        $volei = MemoriaEquipe::create([
            'nome' => 'Associação Atlética Bairro Alto',
            'apelido' => 'As Meninas do Alto',
            'nome_anterior' => 'Clube Recreativo Bairro Alto',
            'escudo' => $this->img('escudo-volei', 400, 400),
            'modalidade_id' => $mods['Voleibol']->id,
            'ano_inicio' => 1986,
            'ano_fim' => 2001,
            'cidade' => 'Bairro Alto',
            'descricao' => 'Geração de ouro do vôlei feminino municipal nos anos 90.',
            'historia' => 'Treinos na quadra descoberta, depois no ginásio. Encerrou as atividades em 2001; a memória segue na Memória do Esporte.',
            'publicado' => true,
        ]);
        $volei->pessoas()->sync([
            $pessoas['maria']->id => ['papel' => 'atleta', 'ano_inicio' => 1988, 'ano_fim' => 1998],
        ]);
        if (!empty($marcos['geracao94'])) {
            $volei->marcos()->sync([$marcos['geracao94']->id]);
        }

        $escolinha = MemoriaEquipe::create([
            'nome' => 'Escolinha Municipal de Futsal',
            'apelido' => 'As Crias do Ginásio',
            'modalidade_id' => $mods['Futsal']->id,
            'ano_inicio' => 1994,
            'cidade' => 'Centro',
            'descricao' => 'Projeto da secretaria que virou o Festival das Escolinhas.',
            'historia' => 'Helena Prado consolidou a metodologia. Turmas de manhã e à tarde no ginásio.',
            'publicado' => true,
        ]);
        $escolinha->pessoas()->sync([
            $pessoas['helena']->id => ['papel' => 'tecnico', 'ano_inicio' => 1994, 'ano_fim' => null],
        ]);
        if (!empty($marcos['festival02'])) {
            $escolinha->marcos()->sync([$marcos['festival02']->id]);
        }

        $rascunho = MemoriaEquipe::create([
            'nome' => 'Time em curadoria',
            'descricao' => 'Cadastro interno ainda sem publicação.',
            'publicado' => false,
            'ano_inicio' => 2020,
        ]);

        return compact('comercial', 'volei', 'escolinha', 'rascunho');
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
                'precisa_identificar' => $i < 6,
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
                'credito' => 'Acervo audiovisual da Secretaria',
                'licenca' => 'uso_institucional',
                'origem' => 'curadoria',
                'status' => 'aprovado',
                'publicado' => true,
                'publicado_em' => now()->subYears(2),
                'aparece_na_timeline' => $key === 'titulo75',
                'transcricao' => $key === 'titulo75'
                    ? 'Locutor: “Gol do Comercial! João Costa na ponta direita, a Vila explode.”'
                    : null,
                'visualizacoes' => 40 + $i * 12,
            ]);
        }

        MemoriaAcervo::create([
            'tipo' => 'audio',
            'titulo' => 'Helena Prado conta as escolinhas no ginásio',
            'alt_text' => 'Depoimento em áudio de Helena Prado',
            'descricao' => 'História oral gravada para a Memória do Esporte.',
            'historia' => 'A técnica lembra o Festival das Escolinhas e o ginásio lotado.',
            'arquivo' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'mime' => 'audio/mpeg',
            'ano' => 2002,
            'local_id' => $locais['ginasio']->id,
            'modalidade_id' => $mods['Futsal']->id,
            'marco_id' => $marcos['festival02']->id,
            'personalidade_id' => $pessoas['helena']->id,
            'fonte' => 'Arquivo da Secretaria de Esportes',
            'fonte_tipo' => 'secretaria',
            'origem' => 'curadoria',
            'status' => 'aprovado',
            'publicado' => true,
            'publicado_em' => now()->subYears(3),
            'aparece_na_timeline' => true,
            'transcricao' => 'O ginásio lotava nas escolinhas. A gente treinou geração atrás de geração naquele piso de taco.',
            'credito' => 'História oral — Secretaria de Esportes',
            'licenca' => 'doacao',
            'visualizacoes' => 128,
        ]);

        $misterio = MemoriaAcervo::query()
            ->where('precisa_identificar', true)
            ->orderBy('id')
            ->first();
        if ($misterio) {
            MemoriaIdentificacao::create([
                'acervo_id' => $misterio->id,
                'nome_sugerido' => 'Primo do João Costa, apelido Zé da Vila',
                'comentario' => 'Está no canto esquerdo, camisa listrada.',
                'doador_nome' => 'Morador da Vila Nova',
                'fingerprint' => hash('sha256', 'demo-identificacao'),
                'status' => 'pendente',
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
                'licenca' => 'arquivo_publico',
                'credito' => 'Jornal da Cidade / Arquivo Municipal',
                'transcricao' => $key === 'ginasio68'
                    ? 'Ata da sessão solene: fica inaugurado o Ginásio Municipal, com a presença do secretário Carlos Eduardo Lima e da comunidade esportiva.'
                    : 'Recorte: a final de 1988 lotou o Estádio da Vila. Pedro Alencar apitou a decisão.',
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
                'doador_email' => Str::slug($p[1], '.') . '@example.com',
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

        $tagFinal = MemoriaTag::query()->firstOrCreate(
            ['nome' => 'final'],
            ['slug' => 'final']
        );
        MemoriaAcervo::query()
            ->where('marco_id', $marcos['titulo75']->id)
            ->get()
            ->each(function (MemoriaAcervo $item) use ($tagFinal) {
                $item->tags()->syncWithoutDetaching([$tagFinal->id]);
            });
    }

    private function catalogarCampos(array $mods, array $locais, array $pessoas, array $marcos, array $equipes, User $admin): void
    {
        $tags = [];
        foreach (['final', 'escolinha', 'inauguracao', 'reforma', 'depoimento', 'recorte', 'selecao'] as $nome) {
            $tags[$nome] = MemoriaTag::query()->firstOrCreate(['nome' => $nome], ['slug' => Str::slug($nome)]);
        }

        MemoriaAcervo::query()->where('marco_id', $marcos['titulo75']->id)->get()->each(function (MemoriaAcervo $item) use ($equipes, $tags) {
            $item->equipes()->syncWithoutDetaching([$equipes['comercial']->id]);
            $item->tags()->syncWithoutDetaching([$tags['final']->id]);
        });
        MemoriaAcervo::query()->where('marco_id', $marcos['geracao94']->id)->get()->each(function (MemoriaAcervo $item) use ($equipes, $tags) {
            $item->equipes()->syncWithoutDetaching([$equipes['volei']->id]);
        });
        MemoriaAcervo::query()->where('marco_id', $marcos['festival02']->id)->get()->each(function (MemoriaAcervo $item) use ($equipes, $tags) {
            $item->equipes()->syncWithoutDetaching([$equipes['escolinha']->id]);
            $item->tags()->syncWithoutDetaching([$tags['escolinha']->id]);
        });

        $timePosado = MemoriaAcervo::query()->where('titulo', 'like', 'Time campeão posado%')->first();
        if ($timePosado) {
            $timePosado->pessoas()->sync([
                $pessoas['joao']->id => ['papel' => 'atleta', 'ordem' => 0, 'observacao' => 'Ponta direita, camisa 7'],
                $pessoas['carlos']->id => ['papel' => 'dirigente', 'ordem' => 1, 'observacao' => 'Ao centro, terno'],
            ]);
            $timePosado->marcos()->syncWithoutDetaching([$marcos['titulo75']->id, $marcos['final88']->id]);
            $timePosado->credito = 'Arquivo do Comercial / Família Costa';
            $timePosado->fotografo = 'João Batista, fotógrafo da Vila';
            $timePosado->licenca = 'doacao';
            $timePosado->data_aproximada = '1975-11-23';
            $timePosado->publicado_em = now()->subYears(1);
            $timePosado->visualizacoes = 312;
            $timePosado->save();
        }

        $incerta = MemoriaAcervo::query()->where('titulo', 'like', 'Vestiário antigo%')->first();
        if ($incerta) {
            $incerta->data_incerta = true;
            $incerta->ano = 1982;
            $incerta->licenca = 'nao_informado';
            $incerta->credito = 'Doação anônima';
            $incerta->save();
        }

        MemoriaAcervo::create([
            'tipo' => 'foto',
            'titulo' => 'Envio pedindo complemento de ficha',
            'alt_text' => 'Foto desfocada do ginásio',
            'descricao' => 'A curadoria pediu ano, local e crédito.',
            'arquivo' => $this->img('complemento-ficha', 1200, 800),
            'mime' => 'image/jpeg',
            'ano' => null,
            'data_incerta' => true,
            'origem' => 'comunidade',
            'doador_nome' => 'Carla Mendes',
            'doador_email' => 'carla@example.com',
            'doador_telefone' => '(15) 98888-2002',
            'status' => 'complemento',
            'motivo_rejeicao' => 'Falta ano aproximado, local e crédito da foto.',
            'publicado' => false,
            'revisado_por' => $admin->id,
            'revisado_em' => now()->subDay(),
        ]);

        $alvoCorrecao = MemoriaAcervo::query()->where('publicado', true)->where('tipo', 'foto')->orderByDesc('id')->first();
        if ($alvoCorrecao) {
            foreach ([
                ['tipo' => 'local', 'local_id' => $locais['ginasio']->id, 'comentario' => 'Isso é o ginásio, não o estádio.', 'valor' => null],
                ['tipo' => 'data', 'ano_sugerido' => 1975, 'comentario' => 'Foi no ano do título.', 'valor' => '1975'],
                ['tipo' => 'equipe', 'equipe_id' => $equipes['comercial']->id, 'comentario' => 'Camisa do Comercial.', 'valor' => null],
                ['tipo' => 'marco', 'marco_id' => $marcos['titulo75']->id, 'comentario' => 'É a festa do título.', 'valor' => null],
                ['tipo' => 'pessoa', 'valor' => 'João Costa na ponta', 'comentario' => 'O da direita é o Garrincha da Vila.'],
                ['tipo' => 'o_que', 'valor' => 'Comemoração no gramado após o apito final', 'comentario' => null],
                ['tipo' => 'outro', 'valor' => 'A faixa da torcida diz Leão da Vila', 'comentario' => 'Dá para ler no canto.'],
            ] as $i => $row) {
                MemoriaCorrecao::create(array_merge($row, [
                    'acervo_id' => $alvoCorrecao->id,
                    'doador_nome' => 'Morador da Vila',
                    'doador_telefone' => '(15) 98888-300' . $i,
                    'fingerprint' => hash('sha256', 'demo-correcao-' . $i),
                    'status' => 'pendente',
                ]));
            }
        }

        $orfas = MemoriaAcervo::query()->where('precisa_identificar', true)->orderBy('id')->get();
        if ($orfas->count() >= 3) {
            MemoriaIdentificacao::create([
                'acervo_id' => $orfas[1]->id,
                'nome_sugerido' => 'Helena Prado jovem',
                'comentario' => 'Banco das escolinhas, anos 90.',
                'doador_nome' => 'Ex-aluna',
                'fingerprint' => hash('sha256', 'demo-id-aprovada'),
                'status' => 'aprovado',
                'personalidade_id' => $pessoas['helena']->id,
                'revisado_por' => $admin->id,
                'revisado_em' => now()->subDays(3),
            ]);
            MemoriaIdentificacao::create([
                'acervo_id' => $orfas[2]->id,
                'nome_sugerido' => 'Nome recusado (piada)',
                'comentario' => 'Sugestão sem fundamento.',
                'doador_nome' => 'Anônimo',
                'fingerprint' => hash('sha256', 'demo-id-rejeitada'),
                'status' => 'rejeitado',
                'motivo_rejeicao' => 'Identificação jocosa, sem evidência.',
                'revisado_por' => $admin->id,
                'revisado_em' => now()->subDays(2),
            ]);
        }

        foreach (['maria' => 48, 'joao' => 73, 'helena' => 31, 'rita' => 22] as $key => $n) {
            for ($i = 0; $i < $n; $i++) {
                MemoriaVotoHall::create([
                    'personalidade_id' => $pessoas[$key]->id,
                    'fingerprint' => hash('sha256', 'demo-voto-' . $key . '-' . $i),
                ]);
            }
        }

        $atleta = Atleta::query()->orderBy('id')->first();
        if ($atleta && isset($pessoas['ana'])) {
            $pessoas['ana']->atleta_id = $atleta->id;
            $pessoas['ana']->save();
        }

        $dupHash = hash('sha256', 'demo-arquivo-duplicado');
        MemoriaAcervo::query()->where('origem', 'curadoria')->where('tipo', 'foto')->limit(2)->get()->each(function (MemoriaAcervo $item) use ($dupHash) {
            $item->hash_arquivo = $dupHash;
            $item->save();
        });

        $momentoGinasio = MemoriaLocalMomento::query()
            ->where('local_id', $locais['ginasio']->id)
            ->where('ano', 1968)
            ->first();
        $fotoInaug = MemoriaAcervo::query()->where('marco_id', $marcos['ginasio68']->id)->where('tipo', 'foto')->first();
        if ($momentoGinasio && $fotoInaug) {
            $momentoGinasio->acervo_id = $fotoInaug->id;
            $momentoGinasio->descricao = 'Liga este momento à peça da inauguração no acervo.';
            $momentoGinasio->save();
        }

        $marcos['titulo75']->equipes()->syncWithoutDetaching([$equipes['comercial']->id]);
        $marcos['geracao94']->equipes()->syncWithoutDetaching([$equipes['volei']->id]);
        $marcos['festival02']->equipes()->syncWithoutDetaching([$equipes['escolinha']->id]);
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

    private function seedRecordes(array $mods, array $pessoas, array $marcos, array $equipes): void
    {
        $campeonato = Campeonato::query()->orderBy('id')->first();

        MemoriaRecorde::create([
            'titulo' => 'Recorde municipal dos 800 m adulto',
            'categoria' => 'tempo',
            'valor' => '2:08.4',
            'unidade' => 'min',
            'ano' => 1999,
            'modalidade_id' => $mods['Atletismo']->id,
            'personalidade_id' => $pessoas['rita']->id,
            'marco_id' => $marcos['recorde99']->id,
            'atleta_nome' => 'Rita Nunes',
            'descricao' => 'Marca ainda vigente na pista municipal, segundo o arquivo da secretaria.',
            'vigente' => true,
            'publicado' => true,
        ]);
        MemoriaRecorde::create([
            'titulo' => 'Artilharia do título de 1975',
            'categoria' => 'artilharia',
            'valor' => '18',
            'unidade' => 'gols',
            'ano' => 1975,
            'modalidade_id' => $mods['Futebol de Campo']->id,
            'personalidade_id' => $pessoas['joao']->id,
            'marco_id' => $marcos['titulo75']->id,
            'atleta_nome' => 'João Costa',
            'descricao' => 'Gols no campeonato municipal que antecedeu o título no Estádio da Vila. Time: Comercial FC.',
            'vigente' => false,
            'publicado' => true,
        ]);
        MemoriaRecorde::create([
            'titulo' => '50 m livre — primeira marca da piscina',
            'categoria' => 'tempo',
            'valor' => '28.12',
            'unidade' => 's',
            'ano' => 2010,
            'modalidade_id' => $mods['Natação']->id,
            'personalidade_id' => $pessoas['ana']->id,
            'marco_id' => $marcos['natacao10']->id,
            'campeonato_id' => $campeonato?->id,
            'atleta_nome' => 'Ana Beatriz Souza',
            'descricao' => 'Primeira atleta da casa em seletiva nacional.',
            'vigente' => false,
            'publicado' => true,
        ]);
        MemoriaRecorde::create([
            'titulo' => 'Pontos em uma final de vôlei municipal',
            'categoria' => 'pontos',
            'valor' => '27',
            'unidade' => 'pontos',
            'ano' => 1994,
            'modalidade_id' => $mods['Voleibol']->id,
            'personalidade_id' => $pessoas['maria']->id,
            'marco_id' => $marcos['geracao94']->id,
            'atleta_nome' => 'Maria das Neves',
            'descricao' => 'Final no Bairro Alto, geração de ouro.',
            'vigente' => false,
            'publicado' => true,
        ]);
        MemoriaRecorde::create([
            'titulo' => 'Rascunho de recorde (não publicado)',
            'categoria' => 'outro',
            'valor' => '—',
            'ano' => 2024,
            'descricao' => 'Aguardando homologação da secretaria.',
            'vigente' => false,
            'publicado' => false,
        ]);
    }

    private function foto(array $d): MemoriaAcervo
    {
        $item = MemoriaAcervo::create([
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
            'credito' => $d['fonte_tipo'] === 'jornal' ? 'Jornal da Cidade' : 'Arquivo da Secretaria de Esportes',
            'fotografo' => in_array($d['fonte_tipo'], ['jornal', 'clube'], true) ? 'Arquivo fotográfico local' : null,
            'licenca' => match ($d['fonte_tipo']) {
                'arquivo_publico' => 'arquivo_publico',
                'jornal' => 'direitos_reservados',
                'clube' => 'doacao',
                default => 'uso_institucional',
            },
            'origem' => $d['origem'],
            'status' => $d['status'],
            'publicado' => $d['publicado'],
            'publicado_em' => !empty($d['publicado']) && ($d['status'] ?? '') === 'aprovado' ? now()->subMonths(6) : null,
            'precisa_identificar' => (bool) ($d['precisa_identificar'] ?? false),
            'aparece_na_timeline' => !empty($d['marco']) && !empty($d['publicado']),
            'visualizacoes' => !empty($d['publicado']) ? random_int(3, 90) : 0,
        ]);
        if (!empty($d['pessoa'])) {
            $item->pessoas()->syncWithoutDetaching([
                $d['pessoa'] => ['papel' => 'atleta', 'ordem' => 0],
            ]);
        }

        return $item;
    }

    private function img(string $seed, int $w, int $h): string
    {
        return 'https://picsum.photos/seed/' . Str::slug($seed) . '/' . $w . '/' . $h;
    }
}
