<script setup>
defineProps({
    type: { type: String, default: 'error' }, // 'error' | 'success' | 'info'
    message: { type: String, default: '' },
});

const styles = {
    error: 'border-red-200 bg-red-50 text-red-700',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    info: 'border-indigo-200 bg-indigo-50 text-indigo-700',
};
</script>

<template>
    <div v-if="message" class="rounded-lg border px-4 py-3 text-sm" :class="styles[type]">
        {{ message }}
    </div>
</template>
