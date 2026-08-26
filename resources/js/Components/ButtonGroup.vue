<script setup>
import { useI18n } from 'vue-i18n';

const props = defineProps({
    modelValue: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

function choose(value) {
    emit('update:modelValue', props.modelValue === value ? null : value);
}

const { t } = useI18n();

const options = [
    { value: 'yes', label: () => t('form.yes'), classes: 'bg-green-600' },
    { value: 'no', label: () => t('form.no'), classes: 'bg-gray-500' },
    { value: 'defect', label: () => t('form.defect'), classes: 'bg-red-600' },
];
</script>

<template>
    <div class="flex gap-2">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="flex-1 rounded px-3 py-2 text-sm font-medium text-white"
            :class="modelValue === option.value ? option.classes : 'bg-gray-200 !text-gray-700'"
            @click="choose(option.value)"
        >
            {{ option.label() }}
        </button>
    </div>
</template>
