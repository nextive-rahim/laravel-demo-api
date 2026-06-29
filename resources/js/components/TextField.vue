<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
    error: { type: String, default: null },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition outline-none placeholder:text-slate-400 focus:ring-2"
            :class="error
                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100'"
        />
        <span v-if="error" class="mt-1.5 block text-sm text-red-600">{{ error }}</span>
    </label>
</template>
