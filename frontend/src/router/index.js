import { createRouter, createWebHistory } from 'vue-router';
import routes from './routes';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...routes
    ]
});

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

            if (user.perfil === 'ARBITRO' && to.path.startsWith('/admin') && !to.path.startsWith('/admin/arbitro/')) {
                next('/admin/arbitro/minhas-partidas');
                return;
            }

            if (user.perfil === 'REDATOR' && to.path.startsWith('/admin') && !to.path.startsWith('/admin/noticias')) {
                next('/admin/noticias');
                return;
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

             if (user.perfil === 'SUPER_ADMIN') {
                 next('/super-admin/dashboard');
                 return;
             }

             if (user.perfil === 'ADMIN') {
                 next('/admin/dashboard');
                 return;
             }

             if (user.perfil === 'REDATOR') {
                 next('/admin/noticias');
                 return;
             }

             if (user.is_manager || user.perfil === 'RESPONSAVEL') {
                 next('/manager');
                 return;
             }
             
             if (user.perfil === 'ATLETA') {
                 next('/athlete/dashboard');
                 return;
             }

             if (user.perfil === 'ARBITRO') {
                 next('/admin/arbitro/minhas-partidas');
                 return;
             }
             
             next('/admin/dashboard');
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
