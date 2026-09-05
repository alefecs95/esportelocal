import { createRouter, createWebHistory } from 'vue-router';
import routes from './routes';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...routes
    ]
});

const homeForUser = (user) => {
    if (user?.perfil === 'SUPER_ADMIN') return '/super-admin/dashboard';
    if (user?.perfil === 'ADMIN') return '/admin/dashboard';
    if (user?.perfil === 'REDATOR') return '/admin/noticias';
    if (user?.is_manager || user?.perfil === 'RESPONSAVEL') return '/manager';
    if (user?.perfil === 'ATLETA') return '/athlete/dashboard';
    if (user?.perfil === 'ARBITRO') return '/admin/arbitro/minhas-partidas';
    return '/portal';
};

router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');
    
    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (!isAuthenticated) {
            next('/login');
        } else {
            // Check Role if needed
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            
            // Force Password Change Check
            if (user.forcar_troca_senha && to.name !== 'ChangePassword') {
                next({ name: 'ChangePassword' });
                return;
            }

            if (to.path.startsWith('/admin')) {
                const perfil = user.perfil;
                if (to.path === '/admin/acesso-negado') {
                    next();
                    return;
                }
                if (perfil === 'ARBITRO') {
                    if (!to.path.startsWith('/admin/arbitro/')) {
                        next('/admin/acesso-negado');
                        return;
                    }
                } else if (perfil === 'REDATOR') {
                    if (!to.path.startsWith('/admin/noticias') && !to.path.startsWith('/admin/memoria')) {
                        next('/admin/acesso-negado');
                        return;
                    }
                } else if (perfil !== 'ADMIN' && perfil !== 'SUPER_ADMIN') {
                    next('/admin/acesso-negado');
                    return;
                }
            }

            if (to.meta.role) {
                // Super Admin
                if (to.meta.role === 'SUPER_ADMIN') {
                    if (user.perfil !== 'SUPER_ADMIN') {
                        next('/admin/dashboard');
                        return;
                    }
                }
                // Manager (RESPONSAVEL or ATLETA with is_manager)
                else if (to.meta.role === 'RESPONSAVEL') {
                    if (user.perfil !== 'RESPONSAVEL' && !user.is_manager) {
                        // If user is ATLETA but not a manager, redirect to athlete dashboard
                        if (user.perfil === 'ATLETA') {
                            next('/athlete/dashboard');
                        } else {
                            next('/portal');
                        }
                        return;
                    }
                } 
                // Athlete
                else if (to.meta.role === 'ATLETA') {
                    if (user.perfil !== 'ATLETA') {
                        next('/portal');
                        return;
                    }
                }
                // Default strict check for other roles
                else if (user.perfil !== to.meta.role) {
                    next('/portal');
                    return;
                }
            }
            next();
        }
    } else {
        // Redirect to dashboard if already logged in and trying to access login
        if (to.path === '/login' && isAuthenticated) {
             const user = JSON.parse(localStorage.getItem('user') || '{}');
             
             if (user.forcar_troca_senha) {
                 next({ name: 'ChangePassword' });
                 return;
             }

             next(homeForUser(user));
        } else {
            next();
        }
    }
});

router.afterEach((to) => {
    const tenantName = localStorage.getItem('tenant_name') || 'SEMEL - Esporte Local';
    const pageTitle = typeof to.meta?.title === 'string' && to.meta.title.trim() !== ''
        ? `${to.meta.title} | ${tenantName}`
        : tenantName;
    document.title = pageTitle;
});

export default router;
