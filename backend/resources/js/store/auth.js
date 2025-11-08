import { defineStore } from 'pinia';
import apiClient, { getCsrfCookie } from '@/services/api';
import router from '@/router'; // Importa o router

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
    }),

    getters: {
        isLoggedIn: (state) => !!state.user,
    },

    actions: {
        async fetchUser() {
            try {
                const response = await apiClient.get('/user');
                this.user = response.data;
            } catch (error) {
                this.user = null;
            }
        },

        async login(credentials) {
            // 1. Pega o CSRF primeiro
            await getCsrfCookie();

            // 2. Tenta logar (usando o axios base do Laravel, não o apiClient)
            // (Assumindo que /login não está em /api)
            await axios.post('http://meu-app.test/login', credentials);

            // 3. Se logou, busca os dados do usuário
            await this.fetchUser();
            
            // 4. Redireciona
            if(this.isLoggedIn) {
                router.push({ name: 'dashboard' });
            }
        },

        async logout() {
            // Chama a rota de logout do Laravel
            await axios.post('http://meu-app.test/logout');
            
            this.user = null;
            router.push({ name: 'login' });
        },
    },
});
