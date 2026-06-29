<script setup>
import { ref } from 'vue';
import { api, ApiError, setToken } from '../api';
import TextField from './TextField.vue';
import AlertMessage from './AlertMessage.vue';
import AuthButton from './AuthButton.vue';

const props = defineProps({
    phone: { type: String, required: true },
});

const emit = defineEmits(['authenticated', 'forgot', 'back']);

const password = ref('');
const loading = ref(false);
const formError = ref(null);

async function submit() {
    loading.value = true;
    formError.value = null;

    try {
        const { token, user } = await api.login({ phone: props.phone, password: password.value });
        setToken(token);
        emit('authenticated', user);
    } catch (e) {
        formError.value = e instanceof ApiError
            ? (e.firstError('phone') || e.message)
            : 'Network error. Please try again.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Enter your password</h1>
        <p class="mt-1 text-sm text-slate-500">Signing in as <span class="font-medium text-slate-700">{{ phone }}</span></p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AlertMessage type="error" :message="formError" />
            <TextField
                v-model="password"
                label="Password"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
            />
            <div class="text-right">
                <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-500" @click="emit('forgot')">
                    Forgot password?
                </button>
            </div>
            <AuthButton :loading="loading">Sign in</AuthButton>
            <AuthButton type="button" variant="ghost" @click="emit('back')">Use a different number</AuthButton>
        </form>
    </div>
</template>
