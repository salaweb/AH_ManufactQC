<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    title: { type: String, required: true },
    items: { type: Array, default: () => [] }, // [{ label, value }]
});

const { t } = useI18n();

const showTable = ref(false);

const max = computed(() => Math.max(1, ...props.items.map((item) => item.value)));

function widthPercent(value) {
    return Math.max(4, Math.round((value / max.value) * 100));
}
</script>

<template>
    <figure class="chart-card">
        <figcaption class="chart-card__header">
            <span class="chart-card__title">{{ title }}</span>
            <button type="button" class="chart-card__toggle" @click="showTable = !showTable">
                {{ showTable ? t('dashboard.chart_view') : t('dashboard.table_view') }}
            </button>
        </figcaption>

        <p v-if="!items.length" class="chart-card__empty">{{ t('dashboard.no_data') }}</p>

        <table v-else-if="showTable" class="chart-card__data-table">
            <thead>
                <tr>
                    <th>{{ t('dashboard.category') }}</th>
                    <th>{{ t('dashboard.count') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.label">
                    <td>{{ item.label }}</td>
                    <td>{{ item.value }}</td>
                </tr>
            </tbody>
        </table>

        <div v-else class="chart-card__bars">
            <div v-for="item in items" :key="item.label" class="chart-row" tabindex="0">
                <span class="chart-row__label">{{ item.label }}</span>
                <span class="chart-row__track">
                    <span class="chart-row__bar" :style="{ width: widthPercent(item.value) + '%' }"></span>
                </span>
                <span class="chart-row__value">{{ item.value }}</span>
            </div>
        </div>
    </figure>
</template>

<style scoped>
.chart-card {
    --surface-1: #fcfcfb;
    --text-primary: #0b0b0b;
    --text-secondary: #52514e;
    --text-muted: #898781;
    --border: rgba(11, 11, 11, 0.1);
    --series-1: #2a78d6;
    --gridline: #e1e0d9;

    margin: 0;
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme='light']) .chart-card {
        --surface-1: #1a1a19;
        --text-primary: #ffffff;
        --text-secondary: #c3c2b7;
        --text-muted: #898781;
        --border: rgba(255, 255, 255, 0.1);
        --series-1: #3987e5;
        --gridline: #2c2c2a;
    }
}

:root[data-theme='dark'] .chart-card {
    --surface-1: #1a1a19;
    --text-primary: #ffffff;
    --text-secondary: #c3c2b7;
    --text-muted: #898781;
    --border: rgba(255, 255, 255, 0.1);
    --series-1: #3987e5;
    --gridline: #2c2c2a;
}

.chart-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.chart-card__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary);
}

.chart-card__toggle {
    font-size: 0.75rem;
    color: var(--text-secondary);
    background: none;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    padding: 0.2rem 0.5rem;
    cursor: pointer;
}

.chart-card__empty {
    color: var(--text-muted);
    font-size: 0.85rem;
}

.chart-card__bars {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chart-row {
    display: grid;
    grid-template-columns: 6rem 1fr 2.5rem;
    align-items: center;
    gap: 0.5rem;
}

.chart-row__label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chart-row__track {
    display: block;
    height: 20px;
    background: var(--gridline);
    border-radius: 4px;
}

.chart-row__bar {
    display: block;
    height: 100%;
    max-height: 20px;
    background: var(--series-1);
    border-radius: 4px;
    transition: opacity 0.15s ease;
}

.chart-row:hover .chart-row__bar,
.chart-row:focus-visible .chart-row__bar {
    opacity: 0.8;
}

.chart-row__value {
    font-size: 0.8rem;
    color: var(--text-primary);
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.chart-card__data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.chart-card__data-table th {
    text-align: left;
    color: var(--text-muted);
    font-weight: 500;
    border-bottom: 1px solid var(--gridline);
    padding: 0.35rem 0;
}

.chart-card__data-table td {
    color: var(--text-primary);
    padding: 0.35rem 0;
    border-bottom: 1px solid var(--gridline);
}
</style>
