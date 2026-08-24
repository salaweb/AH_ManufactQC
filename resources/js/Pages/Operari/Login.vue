<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import FormField from '../../Components/FormField.vue';

const { t } = useI18n();

const form = useForm({
    username: '',
    password: '',
});

function submit() {
    form.post('/operari/login');
}
</script>

<template>
    <LanguageSelector />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <form class="w-full max-w-sm space-y-4 rounded-lg bg-white p-6 shadow" @submit.prevent="submit">
            <h1 class="text-lg font-semibold text-gray-800">{{ t('operari.login_title') }}</h1>

            <FormField
                v-model="form.username"
                :label="t('operari.username')"
                :error="form.errors.username"
            />

            <FormField v-model="form.password" type="password" :label="t('auth.password')" />

            <button type="submit" class="w-full rounded bg-gray-800 py-2 text-white" :disabled="form.processing">
                {{ t('auth.submit') }}
            </button>
        </form>
    </div>
</template>
