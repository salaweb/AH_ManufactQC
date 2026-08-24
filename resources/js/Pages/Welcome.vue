<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../Components/LanguageSelector.vue';

const { t } = useI18n();
</script>

<template>
    <LanguageSelector />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-sm space-y-4 text-center">
            <h1 class="text-2xl font-semibold text-gray-800">AH_ManufactQC</h1>

            <div class="space-y-3">
                <Link
                    href="/operari/login"
                    class="block w-full rounded bg-gray-800 py-3 text-white"
                >
                    {{ t('operari.login_title') }}
                </Link>
                <Link
                    href="/login"
                    class="block w-full rounded border border-gray-300 py-3 text-gray-700"
                >
                    {{ t('auth.admin_login_title') }}
                </Link>
            </div>
        </div>
    </div>
</template>
