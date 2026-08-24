<script setup>
import { useI18n } from 'vue-i18n';
import { setLocale } from '../i18n';

const { locale, t } = useI18n();

function choose(value) {
    setLocale(value);
}
</script>

<template>
    <div class="fixed right-4 top-4 z-50 flex gap-1 rounded-full bg-white p-1 text-sm shadow">
        <button
            type="button"
            class="rounded-full px-3 py-1"
            :class="locale === 'ca' ? 'bg-gray-800 text-white' : 'text-gray-600'"
            @click="choose('ca')"
        >
            {{ t('language.ca') }}
        </button>
        <button
            type="button"
            class="rounded-full px-3 py-1"
            :class="locale === 'es' ? 'bg-gray-800 text-white' : 'text-gray-600'"
            @click="choose('es')"
        >
            {{ t('language.es') }}
        </button>
    </div>
</template>
