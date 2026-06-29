<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    type: { type: String, default: 'submit' },
    variant: { type: String, default: 'primary' }, // 'primary' | 'ghost'
});

const variants = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-500 disabled:bg-indigo-300',
    ghost: 'bg-transparent text-slate-600 hover:text-slate-900',
};
</script>

<template>
    <button
        :type="type"
        :disabled="loading"
        class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed"
        :class="variants[variant]"
    >
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
        </svg>
        <slot />
    </button>
</template>
