<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps({
    backHref: { type: String, default: null },
});

const { t } = useI18n();

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="mb-4 flex items-center justify-between">
        <Link v-if="backHref" :href="backHref" class="text-sm text-gray-500 hover:text-gray-700">
            ← {{ t('common.back') }}
        </Link>
        <span v-else></span>

        <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="logout">
            {{ t('auth.logout') }}
        </button>
    </div>
</template>
