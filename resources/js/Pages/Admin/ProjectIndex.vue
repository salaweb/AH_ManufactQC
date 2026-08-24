<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import FormField from '../../Components/FormField.vue';
import { api } from '../../api';

const { t } = useI18n();

const projects = ref([]);
const sections = ref([]);

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return {
        number: '',
        family: '',
        description: '',
        observations: '',
        section_ids: [],
    };
}

const form = reactive(blankForm());

async function load() {
    projects.value = await api.get('/api/projects');
    sections.value = await api.get('/api/sections');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(project) {
    editingId.value = project.id;
    errors.value = {};
    Object.assign(form, {
        number: project.number,
        family: project.family,
        description: project.description ?? '',
        observations: project.observations ?? '',
        section_ids: project.sections.map((section) => section.id),
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
            await api.put(`/api/projects/${editingId.value}`, form);
        } else {
            await api.post('/api/projects', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(project) {
    if (!confirm(t('admin_projects.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/projects/${project.id}`);
    await load();
}

onMounted(load);
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-3xl space-y-4">
            <Link href="/admin/dashboard" class="text-sm text-gray-500 hover:text-gray-700">
                ← {{ t('common.back') }}
            </Link>

            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_projects.title') }}</h1>
                <button type="button" class="rounded bg-gray-800 px-4 py-2 text-sm text-white" @click="openCreate">
                    {{ t('admin_projects.add') }}
                </button>
            </div>

            <div class="rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_projects.number') }}</th>
                            <th class="px-4 py-2">{{ t('admin_projects.family') }}</th>
                            <th class="px-4 py-2">{{ t('admin_projects.description') }}</th>
                            <th class="px-4 py-2">{{ t('admin_projects.sections') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in projects" :key="project.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ project.number }}</td>
                            <td class="px-4 py-2">{{ project.family }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ project.description }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                <span v-if="project.sections.length">
                                    {{ project.sections.map((s) => s.name).join(', ') }}
                                </span>
                                <span v-else class="text-gray-400">{{ t('admin_projects.no_sections') }}</span>
                            </td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <button type="button" class="text-gray-500 hover:text-gray-800" @click="openEdit(project)">
                                    {{ t('admin_projects.edit') }}
                                </button>
                                <button type="button" class="text-red-500 hover:text-red-700" @click="remove(project)">
                                    {{ t('admin_projects.delete') }}
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
                    {{ editingId ? t('admin_projects.edit') : t('admin_projects.add') }}
                </h2>

                <FormField v-model="form.number" :label="t('admin_projects.number')" :error="errors.number?.[0]" />
                <FormField v-model="form.family" :label="t('admin_projects.family')" :error="errors.family?.[0]" />

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.description') }}</label>
                    <textarea v-model="form.description" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.observations') }}</label>
                    <textarea v-model="form.observations" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.sections') }}</label>
                    <div class="space-y-1 rounded border border-gray-200 p-2">
                        <label v-for="section in sections" :key="section.id" class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" :value="section.id" v-model="form.section_ids" />
                            {{ section.name }}
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="text-sm text-gray-500" @click="closeForm">
                        {{ t('admin_projects.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded bg-gray-800 px-4 py-2 text-sm text-white disabled:opacity-40"
                        :disabled="saving"
                        @click="save"
                    >
                        {{ t('admin_projects.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
