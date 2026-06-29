<script setup>
import { ref } from 'vue';
import { api, ApiError, setToken } from '../api';
import TextField from './TextField.vue';
import AlertMessage from './AlertMessage.vue';
import AuthButton from './AuthButton.vue';

const props = defineProps({
    phone: { type: String, required: true },
});

const emit = defineEmits(['authenticated', 'back']);

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = ref({});
const formError = ref(null);
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};
    formError.value = null;

    try {
        const { token, user } = await api.register({ ...form.value, phone: props.phone });
        setToken(token);
        emit('authenticated', user);
    } catch (e) {
        if (e instanceof ApiError) {
            errors.value = Object.fromEntries(
                Object.entries(e.errors).map(([field, messages]) => [field, messages[0]])
            );
            if (!Object.keys(e.errors).length) {
                formError.value = e.message;
            }
        } else {
            formError.value = 'Network error. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
        <p class="mt-1 text-sm text-slate-500">New here — finish signing up for <span class="font-medium text-slate-700">{{ phone }}</span></p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AlertMessage type="error" :message="formError" />
            <TextField v-model="form.name" label="Full name" autocomplete="name" :error="errors.name" />
            <TextField v-model="form.email" label="Email (optional)" type="email" autocomplete="email" :error="errors.email" />
            <TextField
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="new-password"
                :error="errors.password"
            />
            <TextField
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                autocomplete="new-password"
            />
            <AuthButton :loading="loading">Create account</AuthButton>
            <AuthButton type="button" variant="ghost" @click="emit('back')">Use a different number</AuthButton>
        </form>
    </div>
</template>
