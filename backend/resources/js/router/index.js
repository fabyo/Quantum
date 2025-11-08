import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/store/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/Login.vue'),
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { requiresAuth: true }, // Rota protegida
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation Guard (O "porteiro" do frontend)
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Tenta buscar o usuário (caso tenha recarregado a página)
    if (!authStore.isLoggedIn) {
        await authStore.fetchUser();
    }

    const requiresAuth = to.meta.requiresAuth;

    // Se a rota é protegida e o usuário NÃO está logado
    if (requiresAuth && !authStore.isLoggedIn) {
        next({ name: 'login' }); // Manda pro login
    } else {
        next(); // Deixa passar
    }
});

export default router;
