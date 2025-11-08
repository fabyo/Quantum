<template>
    <form @submit.prevent="handleLogin">
        <div>
            <label>Email</label>
            <input type="email" v-model="form.email" />
        </div>
        <div>
            <label>Senha</label>
            <input type="password" v-model="form.password" />
        </div>
        <button type="submit">Entrar</button>
        <p v-if="error">{{ error }}</p>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/store/auth';

const authStore = useAuthStore();

const form = ref({
    email: '',
    password: '',
});
const error = ref(null);

const handleLogin = async () => {
    error.value = null;
    try {
        // Chama a action do Pinia
        await authStore.login(form.value);
    } catch (e) {
        error.value = 'Email ou senha inválidos.';
    }
};
</script>
