import axios from 'axios';
import { useAuthStore } from '@/store/auth';

// Pega a URL do .env do frontend
const API_URL = import.meta.env.VITE_API_URL;

const apiClient = axios.create({
    baseURL: API_URL + '/api', // Ex: http://127.0.0.1:8000/api
    withCredentials: true,
});

// Interceptor (igual ao anterior)
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && [401, 419].includes(error.response.status)) {
            const authStore = useAuthStore();
            authStore.logout();
        }
        return Promise.reject(error);
    }
);

// Função para pegar o CSRF do Sanctum (agora usando a variável)
export const getCsrfCookie = () => {
    // Aponta para a URL base, não /api
    return axios.get(API_URL + '/sanctum/csrf-cookie');
};

export default apiClient;
