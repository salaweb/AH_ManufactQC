<script setup>
import { useI18n } from 'vue-i18n';

const props = defineProps({
    modelValue: { type: Object, required: true }, // { project_id, from, to }
    projects: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

function update(field, value) {
    emit('update:modelValue', { ...props.modelValue, [field]: value });
}
</script>

<template>
    <div class="filter-bar">
        <select
            class="filter-bar__field"
            :value="modelValue.project_id"
            @change="update('project_id', $event.target.value)"
        >
            <option value="">{{ t('dashboard.all_projects') }}</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.number }}
            </option>
        </select>

        <input
            type="date"
            class="filter-bar__field"
            :value="modelValue.from"
            @change="update('from', $event.target.value)"
        />
        <span class="filter-bar__separator">{{ t('dashboard.date_to') }}</span>
        <input
            type="date"
            class="filter-bar__field"
            :value="modelValue.to"
            @change="update('to', $event.target.value)"
        />
    </div>
</template>

<style scoped>
.filter-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.filter-bar__field {
    border: 1px solid rgba(11, 11, 11, 0.15);
    border-radius: 0.375rem;
    padding: 0.35rem 0.6rem;
    font-size: 0.85rem;
    background: #fcfcfb;
    color: #0b0b0b;
}

.filter-bar__separator {
    font-size: 0.8rem;
    color: #52514e;
}
</style>
