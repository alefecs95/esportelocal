import AdminLayout from '../layouts/AdminLayout.vue';
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue';
import PublicLayout from '../layouts/PublicLayout.vue';
import SumulaEletronica from '../pages/SumulaEletronica.vue';

const routes = [
    // Super Admin Routes
    {
        path: '/super-admin',
        component: SuperAdminLayout,
        meta: { requiresAuth: true, role: 'SUPER_ADMIN' },
        children: [
            {
                path: '',
                redirect: '/super-admin/dashboard'
            },
            {
                path: 'dashboard',
                name: 'SuperAdminDashboard',
                component: () => import('../modules/super_admin/pages/SuperAdminDashboard.vue'),
                meta: { title: 'Dashboard Geral' }
            },
            {
                path: 'prefeituras',
                name: 'PrefeiturasList',
                component: () => import('../modules/super_admin/pages/PrefeiturasList.vue'),
                meta: { title: 'Gestão de Prefeituras' }
            },
            {
                path: 'leads',
                name: 'LeadsList',
                component: () => import('../modules/super_admin/pages/LeadsList.vue'),
                meta: { title: 'Leads e Contatos' }
            },
            {
                path: 'landing-page',
                name: 'SuperAdminLandingPage',
                component: () => import('../modules/super_admin/pages/LandingPageConfig.vue'),
                meta: { title: 'Configurar Landing Page' }
            }
        ]
    },
    // Auth Routes
    {
        path: '/login',
        component: () => import('../layouts/AuthLayout.vue'),
        children: [
            {
                path: '',
                name: 'Login',
                component: () => import('../modules/auth/pages/Login.vue'),
                meta: { title: 'Login' }
            },
            {
                path: 'register',
                name: 'Register',
                component: () => import('../modules/auth/pages/Register.vue'),
                meta: { title: 'Cadastro de Atleta' }
            },
            {
                path: 'forgot-password',
                name: 'ForgotPasswordWhatsApp',
                component: () => import('../modules/auth/pages/ForgotPasswordWhatsApp.vue'),
                meta: { title: 'Recuperar Senha' }
            },
            {
                path: 'reset/:token',
                name: 'ResetPasswordWhatsApp',
                component: () => import('../modules/auth/pages/ResetPasswordWhatsApp.vue'),
                meta: { title: 'Redefinir Senha' }
            },
            {
                path: 'change-password',
                name: 'ChangePassword',
                component: () => import('../modules/auth/pages/ChangePassword.vue'),
                meta: { title: 'Alterar Senha', requiresAuth: true }
            }
        ]
    },
    {
        path: '/dashboard',
        redirect: '/admin/dashboard'
    },
    {
        path: '/arbitro',
        redirect: '/admin/arbitro/minhas-partidas'
    },
    {
        path: '/arbitro/minhas-partidas',
        redirect: '/admin/arbitro/minhas-partidas'
    },
    {
        path: '/arbitro/partidas/:id/operacao',
        redirect: to => `/admin/arbitro/partidas/${to.params.id}/operacao`
    },
    {
        path: '/sumula-eletronica',
        name: 'SumulaEletronica',
        component: SumulaEletronica,
        meta: { title: 'Súmula Eletrônica - Semel.Digital' }
    },
    // Landing Page & Área Pública
    {
        path: '/',
        component: PublicLayout, // The layout will handle showing Landing vs Portal based on subdomain
        children: [
            {
                path: '',
                name: 'Home',
                // Component is dynamically chosen in the router.beforeEach or inside a wrapper component.
                // An elegant Vue way is to use a wrapper component that renders either LandingPage or PortalHome
                component: () => import('../pages/HomeWrapper.vue'),
                meta: { title: 'Início' }
            },
            {
                path: 'noticias',
                name: 'PortalNoticias',
                component: () => import('../modules/portal/pages/PortalNoticias.vue'),
                meta: { title: 'Notícias' }
            },
            {
                path: 'noticias/:slug',
                name: 'PortalNoticiaDetalhe',
                component: () => import('../modules/portal/pages/PortalNoticiaDetalhe.vue'),
                meta: { title: 'Detalhes da Notícia' }
            },
            {
                path: 'campeonatos',
                name: 'PortalCampeonatos',
                component: () => import('../modules/portal/pages/PortalCampeonatos.vue'),
                meta: { title: 'Campeonatos' }
            },
            {
                path: 'campeonatos/:id',
                name: 'PortalCampeonato',
                component: () => import('../modules/portal/pages/PortalCampeonato.vue'),
                meta: { title: 'Detalhes do Campeonato' }
            },
            {
                path: 'partidas',
                name: 'PortalPartidas',
                component: () => import('../modules/portal/pages/PortalPartidas.vue'),
                meta: { title: 'Partidas' }
            },
            {
                path: 'partidas/:id',
                name: 'PortalPartida',
                component: () => import('../modules/portal/pages/PortalPartida.vue'),
                meta: { title: 'Detalhes da Partida' }
            },
            {
                path: 'ranking',
                name: 'PortalRanking',
                component: () => import('../modules/portal/pages/PortalRanking.vue'),
                meta: { title: 'Ranking Municipal' }
            },
            {
                path: 'equipes',
                name: 'PortalEquipes',
                component: () => import('../modules/portal/pages/PortalEquipes.vue'),
                meta: { title: 'Equipes' }
            },
            {
                path: 'equipes/:id',
                name: 'PortalEquipeDetalhe',
                component: () => import('../modules/portal/pages/PortalEquipeDetalhe.vue'),
                meta: { title: 'Detalhes da Equipe' }
            },
            {
                path: 'atletas',
                name: 'PortalAtletas',
                component: () => import('../modules/portal/pages/PortalAtletas.vue'),
                meta: { title: 'Atletas' }
            },
            {
                path: 'atletas/:id',
                name: 'PortalAtletaDetalhe',
                component: () => import('../modules/portal/pages/PortalAtletaDetalhe.vue'),
                meta: { title: 'Detalhes do Atleta' }
            },
            {
                path: 'diretoria',
                name: 'PortalDiretoria',
                component: () => import('../modules/portal/pages/PortalDiretoria.vue'),
                meta: { title: 'Diretoria' }
            }
        ]
    },
    // Área do Responsável (Manager)
    {
        path: '/manager',
        component: () => import('../layouts/ManagerLayout.vue'),
        meta: { requiresAuth: true, role: 'RESPONSAVEL' },
        children: [
            {
                path: '',
                name: 'ManagerDashboard',
                component: () => import('../modules/manager/pages/ManagerDashboard.vue'),
                meta: { title: 'Painel do Responsável' }
            },
            {
                path: 'competicoes',
                name: 'AvailableChampionships',
                component: () => import('../modules/manager/pages/AvailableChampionships.vue'),
                meta: { title: 'Competições Disponíveis' }
            },
            {
                path: 'minhas-equipes',
                name: 'MyTeams',
                component: () => import('../modules/manager/pages/MyTeams.vue'),
                meta: { title: 'Minhas Equipes' }
            },
            {
                path: 'equipes/:id',
                name: 'ManageTeam',
                component: () => import('../modules/manager/pages/ManageTeam.vue'),
                meta: { title: 'Gerenciar Equipe' }
            }
        ]
    },
    // Área do Atleta
    {
        path: '/athlete',
        component: () => import('../modules/athlete_area/layouts/AthleteLayout.vue'),
        meta: { requiresAuth: true, role: 'ATLETA' },
        children: [
            {
                path: 'dashboard',
                name: 'AthleteDashboard',
                component: () => import('../modules/athlete_area/pages/AthleteDashboard.vue'),
                meta: { title: 'Área do Atleta' }
            },
            {
                path: 'profile',
                name: 'AthleteProfile',
                component: () => import('../modules/athlete_area/pages/AthleteProfile.vue'),
                meta: { title: 'Meu Perfil' }
            }
        ]
    },
    // Área Administrativa
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('../modules/dashboard/pages/AdminDashboard.vue'),
                meta: { title: 'Painel Administrativo' }
            },
            {
                path: 'usuarios',
                name: 'AdminUsuarios',
                component: () => import('../modules/admin/pages/AdminUsuarios.vue'),
                meta: { title: 'Usuários e Permissões' }
            },
            {
                path: 'diretoria',
                name: 'AdminDiretoria',
                component: () => import('../modules/admin/pages/AdminDiretoria.vue'),
                meta: { title: 'Gestão de Diretoria' }
            },
            {
                path: 'institucional',
                name: 'Institucional',
                component: () => import('../modules/admin/pages/InstitucionalPage.vue'),
                meta: { title: 'Institucional' }
            },
            {
                path: 'calendario',
                name: 'AdminCalendar',
                component: () => import('../modules/dashboard/pages/AdminCalendar.vue'),
                meta: { title: 'Calendário de Jogos' }
            },
            {
                path: 'temporadas',
                name: 'Temporadas',
                component: () => import('../modules/temporadas/pages/TemporadaList.vue'),
                meta: { title: 'Temporadas' }
            },
            {
                path: 'atletas',
                name: 'Atletas',
                component: () => import('../modules/atletas/pages/AtletaList.vue'),
                meta: { title: 'Atletas' }
            },
            {
                path: 'atletas/novo',
                name: 'AtletaCreate',
                component: () => import('../modules/atletas/pages/AtletaCreate.vue'),
                meta: { title: 'Novo Atleta' }
            },
            {
                path: 'atletas/:id/editar',
                name: 'AtletaEdit',
                component: () => import('../modules/atletas/pages/AtletaEdit.vue'),
                meta: { title: 'Editar Atleta' }
            },
            {
                path: 'modalidades',
                name: 'Modalidades',
                component: () => import('../modules/modalidades/pages/ModalidadeList.vue'),
                meta: { title: 'Modalidades' }
            },
            {
                path: 'modalidades/novo',
                name: 'ModalidadeCreate',
                component: () => import('../modules/modalidades/pages/ModalidadeCreate.vue'),
                meta: { title: 'Nova Modalidade' }
            },
            {
                path: 'modalidades/:id/editar',
                name: 'ModalidadeEdit',
                component: () => import('../modules/modalidades/pages/ModalidadeEdit.vue'),
                meta: { title: 'Editar Modalidade' }
            },
            {
                path: 'entidades',
                name: 'Entidades',
                component: () => import('../modules/entidades/pages/EntidadeManager.vue'),
                meta: { title: 'Base de Clubes' }
            },
            {
                path: 'noticias',
                name: 'Noticias',
                component: () => import('../modules/noticias/pages/NoticiaList.vue'),
                meta: { title: 'Notícias' }
            },
            {
                path: 'noticias/nova',
                name: 'NoticiaCreate',
                component: () => import('../modules/noticias/pages/NoticiaCreate.vue'),
                meta: { title: 'Nova Notícia' }
            },
            {
                path: 'noticias/:id/editar',
                name: 'NoticiaEdit',
                component: () => import('../modules/noticias/pages/NoticiaEdit.vue'),
                meta: { title: 'Editar Notícia' }
            },
            {
                path: 'competicoes',
                name: 'Competicoes',
                component: () => import('../modules/campeonatos/pages/CampeonatoList.vue'),
                meta: { title: 'Competições' }
            },
            {
                path: 'competicoes/novo',
                name: 'CompeticaoCreate',
                component: () => import('../modules/campeonatos/pages/CampeonatoCreate.vue'),
                meta: { title: 'Nova Competição' }
            },
            {
                path: 'competicoes/:id/editar',
                name: 'CompeticaoEdit',
                component: () => import('../modules/campeonatos/pages/CampeonatoEdit.vue'),
                meta: { title: 'Editar Competição' }
            },
            {
                path: 'competicoes/:id/participantes',
                name: 'CompeticaoParticipantes',
                component: () => import('../modules/campeonatos/components/ParticipantesList.vue'),
                meta: { title: 'Participantes da Competição' }
            },
            {
                path: 'inscricoes',
                name: 'Inscricoes',
                component: () => import('../modules/inscricoes/pages/InscricaoList.vue'),
                meta: { title: 'Inscrições' }
            },
            {
                path: 'inscricoes/nova',
                name: 'InscricaoCreate',
                component: () => import('../modules/inscricoes/pages/InscricaoCreate.vue'),
                meta: { title: 'Nova Inscrição' }
            },
            {
                path: 'competicoes/:id/equipes',
                name: 'EquipeList',
                component: () => import('../modules/equipes/pages/EquipeList.vue'),
                meta: { title: 'Equipes' }
            },
            {
                path: 'competicoes/:id/equipes/nova',
                name: 'EquipeCreate',
                component: () => import('../modules/equipes/pages/EquipeCreate.vue'),
                meta: { title: 'Nova Equipe' }
            },
            {
                path: 'equipes/:id',
                name: 'EquipeDetail',
                component: () => import('../modules/equipes/pages/EquipeDetail.vue'),
                meta: { title: 'Detalhes da Equipe' }
            },
            {
                path: 'competicoes/:id/tabela',
                name: 'TabelaView',
                component: () => import('../modules/tabela/pages/TabelaView.vue'),
                meta: { title: 'Tabela de Jogos' }
            },
            {
                path: 'partidas/:id',
                name: 'PartidaDetail',
                component: () => import('../modules/tabela/pages/PartidaDetail.vue'),
                meta: { title: 'Detalhes da Partida' }
            },
            {
                path: 'arbitro/minhas-partidas',
                name: 'MinhasPartidas',
                component: () => import('../modules/arbitro/pages/MinhasPartidas.vue'),
                meta: { title: 'Painel do Árbitro' }
            },
            {
                path: 'arbitro/partidas/:id/operacao',
                name: 'SumulaOperacao',
                component: () => import('../modules/arbitro/pages/SumulaOperacao.vue'),
                meta: { title: 'Súmula Eletrônica' }
            },
            {
                path: 'ranking',
                name: 'RankingGeral',
                component: () => import('../modules/ranking/pages/RankingGeral.vue'),
                meta: { title: 'Ranking Geral' }
            },
            {
                path: 'ranking/modalidade',
                name: 'RankingModalidade',
                component: () => import('../modules/ranking/pages/RankingModalidade.vue'),
                meta: { title: 'Ranking por Modalidade' }
            }
        ]
    }
];

export default routes;
