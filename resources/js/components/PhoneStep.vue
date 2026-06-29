<script setup>
import { ref } from 'vue';
import { api, ApiError } from '../api';
import TextField from './TextField.vue';
import AlertMessage from './AlertMessage.vue';
import AuthButton from './AuthButton.vue';

const props = defineProps({
    initialPhone: { type: String, default: '' },
});

const emit = defineEmits(['found', 'new']);

const phone = ref(props.initialPhone);
const loading = ref(false);
const fieldError = ref(null);
const formError = ref(null);

async function submit() {
    loading.value = true;
    fieldError.value = null;
    formError.value = null;

    try {
        const { exists } = await api.checkPhone(phone.value);
        emit(exists ? 'found' : 'new', phone.value);
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
        <h1 class="text-2xl font-bold text-slate-900">Welcome</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your phone number to sign in or create an account.</p>

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
            <AuthButton :loading="loading">Continue</AuthButton>
        </form>
    </div>
</template>
