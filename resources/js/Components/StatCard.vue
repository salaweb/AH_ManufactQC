<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
});
</script>

<template>
    <div class="stat-tile">
        <p class="stat-tile__label">{{ label }}</p>
        <p class="stat-tile__value">{{ value }}</p>
    </div>
</template>

<style scoped>
.stat-tile {
    --surface-1: #fcfcfb;
    --text-primary: #0b0b0b;
    --text-secondary: #52514e;
    --border: rgba(11, 11, 11, 0.1);

    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme='light']) .stat-tile {
        --surface-1: #1a1a19;
        --text-primary: #ffffff;
        --text-secondary: #c3c2b7;
        --border: rgba(255, 255, 255, 0.1);
    }
}

:root[data-theme='dark'] .stat-tile {
    --surface-1: #1a1a19;
    --text-primary: #ffffff;
    --text-secondary: #c3c2b7;
    --border: rgba(255, 255, 255, 0.1);
}

.stat-tile__label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0 0 0.35rem;
}

.stat-tile__value {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    font-variant-numeric: proportional-nums;
}
</style>
