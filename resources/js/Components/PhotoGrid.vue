<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    photos: { type: Array, default: () => [] }, // [{ id, serie_number, uploaded_at }]
});

const { t } = useI18n();
</script>

<template>
    <figure class="photo-grid-card">
        <figcaption class="photo-grid-card__title">{{ t('dashboard.recent_photos') }}</figcaption>

        <p v-if="!photos.length" class="photo-grid-card__empty">{{ t('dashboard.no_data') }}</p>

        <div v-else class="photo-grid">
            <a
                v-for="photo in photos"
                :key="photo.id"
                :href="`/api/photos/${photo.id}`"
                target="_blank"
                rel="noopener"
                class="photo-grid__item"
            >
                <img :src="`/api/photos/${photo.id}`" :alt="photo.serie_number" loading="lazy" />
                <span class="photo-grid__caption">{{ photo.serie_number }}</span>
            </a>
        </div>
    </figure>
</template>

<style scoped>
.photo-grid-card {
    --surface-1: #fcfcfb;
    --text-primary: #0b0b0b;
    --text-muted: #898781;
    --border: rgba(11, 11, 11, 0.1);

    margin: 0;
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme='light']) .photo-grid-card {
        --surface-1: #1a1a19;
        --text-primary: #ffffff;
        --text-muted: #898781;
        --border: rgba(255, 255, 255, 0.1);
    }
}

:root[data-theme='dark'] .photo-grid-card {
    --surface-1: #1a1a19;
    --text-primary: #ffffff;
    --text-muted: #898781;
    --border: rgba(255, 255, 255, 0.1);
}

.photo-grid-card__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.photo-grid-card__empty {
    color: var(--text-muted);
    font-size: 0.85rem;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 0.5rem;
}

.photo-grid__item {
    display: block;
    text-decoration: none;
}

.photo-grid__item img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    border-radius: 0.375rem;
}

.photo-grid__caption {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.7rem;
    color: var(--text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
