<script setup>
import { onMounted, ref } from 'vue';
import { api, getToken, clearToken } from './api';
import PhoneStep from './components/PhoneStep.vue';
import PasswordStep from './components/PasswordStep.vue';
import SignupStep from './components/SignupStep.vue';
import ForgotStep from './components/ForgotStep.vue';
import ResetStep from './components/ResetStep.vue';
import DashboardView from './components/DashboardView.vue';
import AlertMessage from './components/AlertMessage.vue';

const screen = ref('loading'); // loading | phone | password | signup | forgot | reset | dashboard
const phone = ref('');
const devCode = ref(null);
const user = ref(null);
const notice = ref(null);

onMounted(async () => {
    if (!getToken()) {
        screen.value = 'phone';
        return;
    }

    try {
        const { data } = await api.user();
        user.value = data;
        screen.value = 'dashboard';
    } catch {
        clearToken();
        screen.value = 'phone';
    }
});

function goToPassword(value) {
    phone.value = value;
    notice.value = null;
    screen.value = 'password';
}

function goToSignup(value) {
    phone.value = value;
    screen.value = 'signup';
}

function onAuthenticated(authUser) {
    user.value = authUser;
    notice.value = null;
    screen.value = 'dashboard';
}

function onCodeSent({ phone: value, devCode: code }) {
    phone.value = value;
    devCode.value = code;
    screen.value = 'reset';
}

function onPasswordReset() {
    devCode.value = null;
    notice.value = 'Password reset. You can now sign in with your new password.';
    screen.value = 'password';
}

function resetFlow() {
    user.value = null;
    phone.value = '';
    devCode.value = null;
    notice.value = null;
    screen.value = 'phone';
}
</script>

<template>
    <div class="flex min-h-full">
        <!-- Branding panel (desktop only) -->
        <aside class="hidden w-1/2 flex-col justify-between bg-gradient-to-br from-indigo-600 to-violet-700 p-12 text-white lg:flex">
            <div class="text-lg font-bold tracking-tight">{{ 'Laravel Demo' }}</div>
            <div>
                <h2 class="text-4xl font-bold leading-tight">One number.<br />One account.</h2>
                <p class="mt-4 max-w-md text-indigo-100">
                    Sign in or create an account with just your phone number. Fast, secure, and
                    backed by token authentication.
                </p>
            </div>
            <p class="text-sm text-indigo-200">© {{ 'Laravel Demo' }}</p>
        </aside>

        <!-- Form panel -->
        <main class="flex w-full flex-col items-center justify-center p-6 lg:w-1/2">
            <div class="w-full max-w-md">
                <AlertMessage v-if="notice" type="success" :message="notice" class="mb-6" />

                <div class="rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/60">
                    <div v-if="screen === 'loading'" class="py-12 text-center text-slate-400">Loading…</div>

                    <PhoneStep
                        v-else-if="screen === 'phone'"
                        :initial-phone="phone"
                        @found="goToPassword"
                        @new="goToSignup"
                    />

                    <PasswordStep
                        v-else-if="screen === 'password'"
                        :phone="phone"
                        @authenticated="onAuthenticated"
                        @forgot="screen = 'forgot'"
                        @back="resetFlow"
                    />

                    <SignupStep
                        v-else-if="screen === 'signup'"
                        :phone="phone"
                        @authenticated="onAuthenticated"
                        @back="resetFlow"
                    />

                    <ForgotStep
                        v-else-if="screen === 'forgot'"
                        :initial-phone="phone"
                        @sent="onCodeSent"
                        @back="screen = 'password'"
                    />

                    <ResetStep
                        v-else-if="screen === 'reset'"
                        :phone="phone"
                        :dev-code="devCode"
                        @done="onPasswordReset"
                        @back="screen = 'forgot'"
                    />

                    <DashboardView
                        v-else-if="screen === 'dashboard'"
                        :user="user"
                        @logout="resetFlow"
                    />
                </div>
            </div>
        </main>
    </div>
</template>
