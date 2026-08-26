<script setup>
defineProps({
    variant: { type: String, default: 'primary' }, // primary | danger | outline | ghost | ghost-danger
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});

const variantClasses = {
    primary: 'rounded bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700',
    danger: 'rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700',
    outline: 'rounded border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:border-gray-400 hover:bg-gray-50',
    ghost: 'text-sm text-gray-500 hover:text-gray-700',
    'ghost-danger': 'text-sm text-red-500 hover:text-red-700',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="transition duration-100 ease-out active:scale-90 active:brightness-90 disabled:pointer-events-none disabled:opacity-40"
        :class="variantClasses[variant]"
    >
        <slot />
    </button>
</template>
