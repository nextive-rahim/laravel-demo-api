<script setup>
import { ref } from 'vue';
import { api, ApiError } from '../api';
import TextField from './TextField.vue';
import AlertMessage from './AlertMessage.vue';
import AuthButton from './AuthButton.vue';

const props = defineProps({
    phone: { type: String, required: true },
    devCode: { type: String, default: null },
});

const emit = defineEmits(['done', 'back']);

const form = ref({
    code: props.devCode || '',
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
        await api.resetPassword({ ...form.value, phone: props.phone });
        emit('done');
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
        <h1 class="text-2xl font-bold text-slate-900">Enter the code</h1>
        <p class="mt-1 text-sm text-slate-500">Sent to <span class="font-medium text-slate-700">{{ phone }}</span></p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AlertMessage
                v-if="devCode"
                type="info"
                :message="`Dev mode: your code is ${devCode}`"
            />
            <AlertMessage type="error" :message="formError" />
            <TextField v-model="form.code" label="Reset code" placeholder="6-digit code" :error="errors.code" />
            <TextField
                v-model="form.password"
                label="New password"
                type="password"
                autocomplete="new-password"
                :error="errors.password"
            />
            <TextField
                v-model="form.password_confirmation"
                label="Confirm new password"
                type="password"
                autocomplete="new-password"
            />
            <AuthButton :loading="loading">Reset password</AuthButton>
            <AuthButton type="button" variant="ghost" @click="emit('back')">Back</AuthButton>
        </form>
    </div>
</template>
