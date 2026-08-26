<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const sections = ref([]);
const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { name: '', description: '', order: 0 };
}

const form = reactive(blankForm());

async function load() {
    sections.value = await api.get('/api/sections');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(section) {
    editingId.value = section.id;
    errors.value = {};
    Object.assign(form, {
        name: section.name,
        description: section.description ?? '',
        order: section.order,
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
        if (editingId.value) {
            await api.put(`/api/sections/${editingId.value}`, form);
        } else {
            await api.post('/api/sections', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(section) {
    if (!confirm(t('admin_sections.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/sections/${section.id}`);
    await load();
}

function goToQuestions(section) {
    router.visit(`/admin/sections/${section.id}/questions`);
}

onMounted(load);
</script>

<template>
    <LanguageSelector />
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-5xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_sections.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_sections.add') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_sections.name') }}</th>
                            <th class="px-4 py-2">{{ t('admin_sections.description') }}</th>
                            <th class="px-4 py-2">{{ t('admin_sections.questions_count') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="section in sections" :key="section.id" class="cursor-pointer border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium" @click="goToQuestions(section)">{{ section.name }}</td>
                            <td class="px-4 py-2 text-gray-600" @click="goToQuestions(section)">{{ section.description }}</td>
                            <td class="px-4 py-2 text-gray-600" @click="goToQuestions(section)">
                                {{ section.questions_count }}
                            </td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <Button variant="ghost" @click="goToQuestions(section)">
                                    {{ t('admin_sections.manage_questions') }}
                                </Button>
                                <Button variant="ghost" @click="openEdit(section)">
                                    {{ t('admin_sections.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(section)">
                                    {{ t('admin_sections.delete') }}
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="formOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md space-y-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ editingId ? t('admin_sections.edit') : t('admin_sections.add') }}
                </h2>

                <FormField v-model="form.name" :label="t('admin_sections.name')" :error="errors.name?.[0]" />

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_sections.description') }}</label>
                    <textarea v-model="form.description" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_sections.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_sections.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
