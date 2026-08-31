<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\AtletaController;
use App\Http\Controllers\Api\V1\ModalidadeController;
use App\Http\Controllers\Api\V1\CampeonatoController;
use App\Http\Controllers\Api\V1\InscricaoController;
use App\Http\Controllers\Api\V1\FiltroController;
use App\Http\Controllers\Api\V1\EquipeController;
use App\Http\Controllers\Api\V1\EntidadeController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ManagerController;
use App\Http\Controllers\Api\V1\AthleteAreaController;
use App\Http\Controllers\TabelaController;
use App\Http\Controllers\ArbitroController;
use App\Http\Controllers\TemporadaController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\CompeticaoEstruturaController;
use App\Http\Controllers\EventoPartidaController;
use App\Http\Controllers\PartidaController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Admin\PrefeituraController;
use App\Http\Controllers\Api\V1\NoticiaController;
use App\Http\Controllers\Api\V1\InstitucionalController;
use App\Http\Controllers\Api\V1\DiretoriaController;
use App\Http\Controllers\Api\V1\WhatsAppNotificationController;
use App\Http\Controllers\Api\V1\PublicMediaController;

if (config('app.debug')) {
    require __DIR__ . '/debug_img.php';
}

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

if (config('app.debug')) {
    Route::get('/debug-db', function () {
        try {
            DB::connection()->getPdo();
            return [
                'database' => DB::connection()->getDatabaseName(),
                'config_db' => config('database.connections.mysql.database'),
                'env_db' => env('DB_DATABASE'),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    });
}

// Rotas Assinadas para PDF (Protegidas) - compatibilidade legada
Route::group([], function () {
    Route::get('signed/pdf/carteirinha/{atletaId}/{campeonatoId}', [PdfController::class, 'gerarCarteirinha'])->name('pdf.carteirinha');
    Route::get('signed/pdf/sumula/{partidaId}', [PdfController::class, 'gerarSumula'])->name('pdf.sumula');
    Route::get('signed/pdf/participantes/{campeonatoId}', [PdfController::class, 'gerarListaInscritos'])->name('pdf.participantes');
    Route::get('signed/pdf/equipe-atletas/{equipeId}', [PdfController::class, 'gerarEquipeAtletas'])->name('pdf.equipe-atletas');
});

// Rotas Assinadas para PDF (Protegidas) - versão /v1 (alias)
Route::prefix('v1')->group(function () {
    Route::get('signed/pdf/carteirinha/{atletaId}/{campeonatoId}', [PdfController::class, 'gerarCarteirinha'])->name('pdf.v1.carteirinha');
    Route::get('signed/pdf/sumula/{partidaId}', [PdfController::class, 'gerarSumula'])->name('pdf.v1.sumula');
    Route::get('signed/pdf/participantes/{campeonatoId}', [PdfController::class, 'gerarListaInscritos'])->name('pdf.v1.participantes');
    Route::get('signed/pdf/equipe-atletas/{equipeId}', [PdfController::class, 'gerarEquipeAtletas'])->name('pdf.v1.equipe-atletas');
});

Route::prefix('v1')->group(function () {
    // Institucional - Configurações da Prefeitura e Diretoria
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('institucional', [InstitucionalController::class, 'show']);
        Route::post('institucional', [InstitucionalController::class, 'update']);
        Route::post('institucional/integracoes/whatsapp/testar', [InstitucionalController::class, 'testWhatsAppConnection']);
        Route::apiResource('diretoria', DiretoriaController::class);
    });

    // Public Diretoria
    Route::get('public/diretoria', [DiretoriaController::class, 'index']);
    Route::get('public/media/{path}', [PublicMediaController::class, 'show'])->where('path', '.*');

    // Admin Routes (Global)
    Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckSuperAdmin::class])
        ->prefix('admin')
        ->group(function () {
            Route::apiResource('prefeituras', PrefeituraController::class);
            Route::get('prefeituras/{id}/historico-consultas', [PrefeituraController::class, 'historicoConsultas']);
            Route::apiResource('leads', \App\Http\Controllers\Admin\LeadController::class)->except(['store']);
            Route::get('landing-page', [\App\Http\Controllers\Admin\LandingPageSettingController::class, 'show']);
            Route::put('landing-page', [\App\Http\Controllers\Admin\LandingPageSettingController::class, 'update']);
        });

    // Public Landing Page Settings & Leads
    Route::get('/public/landing-page', [\App\Http\Controllers\Admin\LandingPageSettingController::class, 'show']);
    Route::post('/public/leads', [\App\Http\Controllers\Admin\LeadController::class, 'store']);

    // Tenant Public Info
    Route::get('/tenant', [TenantController::class, 'show']);

    // Auth
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('password/forgot-whatsapp', [AuthController::class, 'forgotPasswordWhatsApp']);
    Route::post('password/reset-by-token', [AuthController::class, 'resetPasswordByToken']);
    Route::get('notifications/unsubscribe/{token}', [WhatsAppNotificationController::class, 'unsubscribe']);

    // Temporadas
    Route::put('temporadas/{id}/ativar', [TemporadaController::class, 'ativar']);
    Route::apiResource('temporadas', TemporadaController::class);

    Route::get('users/responsaveis', [UserController::class, 'searchResponsavel']);
    Route::get('users/arbitros', [UserController::class, 'getArbitros']);

    Route::get('atletas/consulta-cpf/{cpf}', [AtletaController::class, 'consultarCpf']);
    Route::post('atletas/consulta-titulo', [AtletaController::class, 'consultarTitulo']);
    Route::apiResource('atletas', AtletaController::class);
    Route::apiResource('modalidades', ModalidadeController::class);
    
    Route::get('filtros/cidades-eleitorais', [FiltroController::class, 'cidadesEleitorais']);
    Route::get('filtros/bairros-eleitorais', [FiltroController::class, 'bairrosEleitorais']);
    
    // Rota de Simulação
    Route::post('competicoes/simular-elegibilidade', [CampeonatoController::class, 'simularElegibilidade']);
    
    // Formatos de Campeonato
    Route::get('formatos-campeonato', [CampeonatoController::class, 'getFormatos']);

    // Rotas de Campeonatos (Legado e Novo)
    Route::get('campeonatos/{id}/inscricoes', [InscricaoController::class, 'indexByCampeonato']);
    Route::apiResource('campeonatos', CampeonatoController::class);
    // Alias para competicoes (opcional, mas bom para consistência)
    Route::apiResource('competicoes', CampeonatoController::class)->parameters(['competicoes' => 'campeonato']);

    Route::post('inscricoes', [InscricaoController::class, 'store']);
    Route::put('inscricoes/{id}', [InscricaoController::class, 'update']);

    // Entidades (Clubes)
    Route::apiResource('entidades', EntidadeController::class);

    // Equipes
    Route::get('equipes/{id}', [EquipeController::class, 'show']);
    Route::get('campeonatos/{id}/equipes', [EquipeController::class, 'indexByCampeonato']);

    // Tabela e Partidas
    Route::post('campeonatos/{id}/gerar-tabela', [TabelaController::class, 'gerarTabela']);
    Route::get('campeonatos/{id}/tabela', [TabelaController::class, 'listarTabela']);
    Route::put('partidas/{id}/placar', [TabelaController::class, 'atualizarPlacar']);
    // Corrida
    Route::post('campeonatos/{id}/resultados-corrida', [TabelaController::class, 'salvarResultadosCorrida']);

    // Módulo Árbitro
    Route::get('arbitro/minhas-partidas', [ArbitroController::class, 'minhasPartidas']);
    Route::put('arbitro/partidas/{id}/placar', [ArbitroController::class, 'atualizarPlacar']);
    Route::get('arbitro/partidas/{id}/escalacao', [ArbitroController::class, 'getEscalacao']);
    Route::put('arbitro/partidas/{id}/escalacao', [ArbitroController::class, 'salvarEscalacao']);
    Route::post('arbitro/partidas/{id}/eventos/lote', [ArbitroController::class, 'storeEventosLote']);
    Route::post('arbitro/partidas/{id}/eventos', [ArbitroController::class, 'storeEvento']);

    // Ranking
    Route::get('ranking/temporada-ativa', [RankingController::class, 'temporadaAtiva']);
    Route::post('ranking/recalcular', [RankingController::class, 'recalcular'])->middleware('admin');
    Route::get('ranking/{temporada_id}', [RankingController::class, 'index']);
    Route::get('ranking/{temporada_id}/modalidade/{modalidade_id}', [RankingController::class, 'porModalidade']);
    Route::get('ranking/{temporada_id}/top10', [RankingController::class, 'top10']);

    // Eventos da Partida
    Route::get('partidas/{id}', [PartidaController::class, 'show']);
    Route::get('partidas/{id}/eventos', [EventoPartidaController::class, 'index']);
    
    // Dashboard Admin
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('dashboard/calendar', [DashboardController::class, 'getCalendarEvents']);

    // Módulo Público (Portal)
    Route::get('public/home-data', [PublicController::class, 'getHomeData']);
    Route::get('public/noticias', [NoticiaController::class, 'index']);
    Route::get('public/noticias/{slug}', [NoticiaController::class, 'show']);
    Route::get('public/noticias/{id}/comentarios', [\App\Http\Controllers\Api\V1\ComentarioController::class, 'index']);
    Route::get('public/campeonatos', [CampeonatoController::class, 'indexPublic']);
    Route::get('public/campeonatos/{id}/banner', [PublicController::class, 'getCampeonatoBanner']);
    Route::get('public/campeonatos/{id}/tabela', [TabelaController::class, 'listarTabela']);
    Route::get('public/campeonatos/{id}/artilharia', [PublicController::class, 'getArtilharia']);
    Route::get('public/campeonatos/{id}/cartoes', [PublicController::class, 'getCartoes']);

    // Equipes
    Route::get('public/equipes', [PublicController::class, 'getEquipes']);
    Route::get('public/equipes/{id}', [PublicController::class, 'getEquipeDetalhe']);

    // Atletas
    Route::get('public/atletas', [PublicController::class, 'getAtletas']);
    Route::get('public/atletas/{id}', [PublicController::class, 'getAtletaDetalhe']);

    Route::get('public/partidas', [PublicController::class, 'getPartidas']);
    Route::get('public/partidas/{id}', [PartidaController::class, 'show']);
    Route::get('public/partidas/{id}/eventos', [EventoPartidaController::class, 'index']);

    // Rotas protegidas que geram URLs para PDF (agora exigem autenticação)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('update-password', [AuthController::class, 'updatePassword']);
        Route::get('usuarios', [UserController::class, 'adminIndex']);
        Route::post('usuarios', [UserController::class, 'adminStore']);
        Route::put('usuarios/{id}', [UserController::class, 'adminUpdate']);
        Route::delete('usuarios/{id}', [UserController::class, 'adminDestroy']);
        Route::post('whatsapp/send', [WhatsAppNotificationController::class, 'send']);
        Route::put('whatsapp/preferences', [WhatsAppNotificationController::class, 'updatePreference']);
        
        // Gerar URLs para PDF
        Route::get('campeonatos/{id}/pdf-inscritos', [PdfController::class, 'getUrlListaInscritos']);
        Route::get('atletas/{atletaId}/pdf-carteirinha', [PdfController::class, 'getUrlCarteirinha']); // Precisa passar ?campeonato_id=X na query
        Route::get('atletas/{atletaId}/pdf-ficha', [PdfController::class, 'gerarFichaAtleta']);
        Route::get('partidas/{id}/pdf-sumula', [PdfController::class, 'getUrlSumula']);
        Route::get('equipes/{id}/pdf-atletas', [PdfController::class, 'getUrlEquipeAtletas']);

        // Manager Routes
        Route::get('manager/campeonatos', [ManagerController::class, 'availableCampeonatos']);
        Route::get('manager/equipes', [EquipeController::class, 'indexByResponsavel']);
        Route::put('equipes/{id}/status', [EquipeController::class, 'updateStatus'])->middleware('admin');
        Route::put('atletas/{id}/status', [AtletaController::class, 'updateStatus']);
        Route::post('atletas/{id}/revalidar-titulo', [AtletaController::class, 'revalidarTitulo']);
        Route::post('atletas/{id}/aprovar-dados-eleitorais-manual', [AtletaController::class, 'aprovarDadosEleitoraisManualmente']);
        Route::post('atletas/{id}/reset-password', [AtletaController::class, 'resetPassword']);
        Route::post('atletas/{id}/gerar-usuario', [AtletaController::class, 'gerarUsuario']);
        
        // Equipes Management
        Route::post('equipes', [EquipeController::class, 'store']);
        Route::get('equipes/{id}/atletas-elegiveis', [EquipeController::class, 'atletasElegiveis']);
        Route::post('equipes/{id}/adicionar-atleta', [EquipeController::class, 'addAtleta']);
        Route::put('equipes/{id}/atletas/{atleta_id}/status', [EquipeController::class, 'updateAtletaStatus']);
        Route::put('equipes/{id}/atletas/status-lote', [EquipeController::class, 'updateAtletasStatusLote']);
        Route::delete('equipes/{id}/remover-atleta/{atleta_id}', [EquipeController::class, 'removeAtleta']);

        Route::post('eventos-partida', [EventoPartidaController::class, 'store']);
        Route::delete('eventos-partida/{id}', [EventoPartidaController::class, 'destroy']);

        Route::post('upload-image', [UploadController::class, 'store']);
        Route::post('upload-media', [UploadController::class, 'storeMedia']);

        Route::get('competicoes/{id}/estrutura', [CompeticaoEstruturaController::class, 'show']);
        Route::put('competicoes/{id}/estrutura', [CompeticaoEstruturaController::class, 'update']);
        Route::post('competicoes/{id}/confrontos/sugerir', [CompeticaoEstruturaController::class, 'sugerirConfrontos']);
        Route::post('competicoes/{id}/resolver-classificados', [CompeticaoEstruturaController::class, 'resolverClassificados']);
        Route::get('fases/regras-classificacao/criterios', [CompeticaoEstruturaController::class, 'criteriosClassificacao']);
        Route::get('fases/{id}/regras-classificacao', [CompeticaoEstruturaController::class, 'getRegrasClassificacao']);
        Route::put('fases/{id}/regras-classificacao', [CompeticaoEstruturaController::class, 'putRegrasClassificacao']);
        Route::get('fases/{id}/classificacao', [CompeticaoEstruturaController::class, 'getClassificacaoFase']);

        // Notícias
        Route::apiResource('noticias', NoticiaController::class);
        Route::patch('noticias/{id}/toggle-destaque', [NoticiaController::class, 'toggleDestaque']);
        Route::post('noticias/{id}/comentarios', [\App\Http\Controllers\Api\V1\ComentarioController::class, 'store']);
        Route::get('noticias/{id}/comentarios-admin', [\App\Http\Controllers\Api\V1\ComentarioController::class, 'indexAdmin']);
        Route::delete('comentarios/{id}', [\App\Http\Controllers\Api\V1\ComentarioController::class, 'destroy']);
        Route::apiResource('categorias', \App\Http\Controllers\Api\V1\CategoriaController::class);

        // Athlete Area Routes
        Route::get('athlete/dashboard', [AthleteAreaController::class, 'dashboard']);
        Route::get('athlete/profile', [AthleteAreaController::class, 'profile']);
        Route::put('athlete/profile', [AthleteAreaController::class, 'updateProfile']);
        Route::post('athlete/photo', [AthleteAreaController::class, 'uploadPhoto']);
        Route::post('athlete/documents', [AthleteAreaController::class, 'uploadDocument']);
        Route::delete('athlete/documents/{id}', [AthleteAreaController::class, 'deleteDocument']);
        Route::post('athlete/convites/{id}/responder', [AthleteAreaController::class, 'responderConvite']);
    });
});
