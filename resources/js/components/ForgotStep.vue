<script setup>
import { ref } from 'vue';
import { api, ApiError } from '../api';
import TextField from './TextField.vue';
import AlertMessage from './AlertMessage.vue';
import AuthButton from './AuthButton.vue';

const props = defineProps({
    initialPhone: { type: String, default: '' },
});

const emit = defineEmits(['sent', 'back']);

const phone = ref(props.initialPhone);
const loading = ref(false);
const fieldError = ref(null);
const formError = ref(null);

async function submit() {
    loading.value = true;
    fieldError.value = null;
    formError.value = null;

    try {
        const { code } = await api.forgotPassword(phone.value);
        // `code` is only returned outside production (no SMS provider wired up).
        emit('sent', { phone: phone.value, devCode: code });
    } catch (e) {
        if (e instanceof ApiError) {
            fieldError.value = e.firstError('phone');
            if (!fieldError.value) {
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
        <h1 class="text-2xl font-bold text-slate-900">Reset your password</h1>
        <p class="mt-1 text-sm text-slate-500">We'll send a one-time code to your phone.</p>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <AlertMessage type="error" :message="formError" />
            <TextField
                v-model="phone"
                label="Phone number"
                type="tel"
                placeholder="017XXXXXXXX"
                autocomplete="tel"
                :error="fieldError"
            />
            <AuthButton :loading="loading">Send reset code</AuthButton>
            <AuthButton type="button" variant="ghost" @click="emit('back')">Back to sign in</AuthButton>
        </form>
    </div>
</template>
