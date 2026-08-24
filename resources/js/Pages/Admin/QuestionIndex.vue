<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import FormField from '../../Components/FormField.vue';
import { api } from '../../api';

const props = defineProps({
    sectionId: { type: [Number, String], required: true },
});

const { t } = useI18n();

const section = ref(null);
const questions = ref([]);
const categories = ['estetica', 'funcional_mecanica', 'electronica'];

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { text: '', category: categories[0], order: 0, is_required: true };
}

const form = reactive(blankForm());

async function load() {
    section.value = await api.get(`/api/sections/${props.sectionId}`);
    questions.value = await api.get(`/api/questions?section_id=${props.sectionId}`);
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(question) {
    editingId.value = question.id;
    errors.value = {};
    Object.assign(form, {
        text: question.text,
        category: question.category,
        order: question.order,
        is_required: question.is_required,
    });
    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
}

async function save() {
    saving.value = true;
    errors.value = {};

    try {
        const payload = { ...form, section_id: props.sectionId };

        if (editingId.value) {
            await api.put(`/api/questions/${editingId.value}`, payload);
        } else {
            await api.post('/api/questions', payload);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(question) {
    if (!confirm(t('admin_questions.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/questions/${question.id}`);
    await load();
}

onMounted(load);
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-3xl space-y-4">
            <Link href="/admin/sections" class="text-sm text-gray-500 hover:text-gray-700">
                ← {{ t('common.back') }}
            </Link>

            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ t('admin_questions.title') }} — {{ section?.name }}
                </h1>
                <button type="button" class="rounded bg-gray-800 px-4 py-2 text-sm text-white" @click="openCreate">
                    {{ t('admin_questions.add') }}
                </button>
            </div>

            <div class="rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_questions.text') }}</th>
                            <th class="px-4 py-2">{{ t('admin_questions.category') }}</th>
                            <th class="px-4 py-2">{{ t('admin_questions.order') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="question in questions" :key="question.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ question.text }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ t(`category.${question.category}`) }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ question.order }}</td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <button type="button" class="text-gray-500 hover:text-gray-800" @click="openEdit(question)">
                                    {{ t('admin_questions.edit') }}
                                </button>
                                <button type="button" class="text-red-500 hover:text-red-700" @click="remove(question)">
                                    {{ t('admin_questions.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="formOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md space-y-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ editingId ? t('admin_questions.edit') : t('admin_questions.add') }}
                </h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_questions.text') }}</label>
                    <textarea v-model="form.text" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                    <p v-if="errors.text" class="mt-1 text-sm text-red-600">{{ errors.text[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_questions.category') }}</label>
                    <select v-model="form.category" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ t(`category.${cat}`) }}</option>
                    </select>
                </div>

                <FormField v-model="form.order" type="number" :label="t('admin_questions.order')" />

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.is_required" />
                    {{ t('admin_questions.required') }}
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="text-sm text-gray-500" @click="closeForm">
                        {{ t('admin_questions.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded bg-gray-800 px-4 py-2 text-sm text-white disabled:opacity-40"
                        :disabled="saving"
                        @click="save"
                    >
                        {{ t('admin_questions.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
