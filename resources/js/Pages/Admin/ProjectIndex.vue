<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import FormField from '../../Components/FormField.vue';
import { api } from '../../api';

const { t } = useI18n();

const projects = ref([]);
const sections = ref([]);
const families = ref([]);
const descriptionTags = ref([]);

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

const newFamilyName = ref('');
const newTagName = ref('');
const newFamilyError = ref('');
const newTagError = ref('');
const tagQuery = ref('');

const tagResults = computed(() => {
    const query = tagQuery.value.trim().toLowerCase();

    if (!query) {
        return [];
    }

    return descriptionTags.value
        .filter((tag) => !form.description_tag_ids.includes(tag.id))
        .filter((tag) => tag.name.toLowerCase().includes(query))
        .slice(0, 8);
});

function tagName(id) {
    return descriptionTags.value.find((tag) => tag.id === id)?.name ?? '';
}

function selectTag(tag) {
    form.description_tag_ids.push(tag.id);
    tagQuery.value = '';
}

function removeSelectedTag(id) {
    form.description_tag_ids = form.description_tag_ids.filter((tagId) => tagId !== id);
}

function moveTagUp(index) {
    if (index === 0) {
        return;
    }

    const ids = form.description_tag_ids;
    [ids[index - 1], ids[index]] = [ids[index], ids[index - 1]];
}

function moveTagDown(index) {
    const ids = form.description_tag_ids;

    if (index === ids.length - 1) {
        return;
    }

    [ids[index], ids[index + 1]] = [ids[index + 1], ids[index]];
}

function blankForm() {
    return {
        number: '',
        family_id: '',
        observations: '',
        section_ids: [],
        description_tag_ids: [],
    };
}

const form = reactive(blankForm());

async function load() {
    projects.value = await api.get('/api/projects');
    sections.value = await api.get('/api/sections');
    families.value = await api.get('/api/families');
    descriptionTags.value = await api.get('/api/description-tags');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    newFamilyName.value = '';
    newTagName.value = '';
    newFamilyError.value = '';
    newTagError.value = '';
    tagQuery.value = '';
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(project) {
    editingId.value = project.id;
    errors.value = {};
    newFamilyName.value = '';
    newTagName.value = '';
    newFamilyError.value = '';
    newTagError.value = '';
    tagQuery.value = '';
    Object.assign(form, {
        number: project.number,
        family_id: project.family_id,
        observations: project.observations ?? '',
        section_ids: project.sections.map((section) => section.id),
        description_tag_ids: project.description_tags.map((tag) => tag.id),
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

async function addFamily() {
    if (!newFamilyName.value.trim()) {
        return;
    }

    newFamilyError.value = '';

    try {
        const family = await api.post('/api/families', { name: newFamilyName.value.trim() });
        families.value.push(family);
        families.value.sort((a, b) => a.name.localeCompare(b.name));
        form.family_id = family.id;
        newFamilyName.value = '';
    } catch (error) {
        newFamilyError.value = error.data?.errors?.name?.[0] ?? t('admin_projects.add_failed');
    }
}

async function addTag() {
    if (!newTagName.value.trim()) {
        return;
    }

    newTagError.value = '';

    try {
        const tag = await api.post('/api/description-tags', { name: newTagName.value.trim() });
        descriptionTags.value.push(tag);
        descriptionTags.value.sort((a, b) => a.name.localeCompare(b.name));
        form.description_tag_ids.push(tag.id);
        newTagName.value = '';
    } catch (error) {
        newTagError.value = error.data?.errors?.name?.[0] ?? t('admin_projects.add_failed');
    }
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
                            <td class="px-4 py-2">{{ project.family?.name }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                {{ project.description_tags.map((tag) => tag.name).join(' ') }}
                            </td>
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

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.family') }}</label>
                    <select v-model="form.family_id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="" disabled>—</option>
                        <option v-for="family in families" :key="family.id" :value="family.id">
                            {{ family.name }}
                        </option>
                    </select>
                    <p v-if="errors.family_id" class="mt-1 text-sm text-red-600">{{ errors.family_id[0] }}</p>
                    <div class="mt-1 flex gap-2">
                        <input
                            v-model="newFamilyName"
                            type="text"
                            :placeholder="t('admin_projects.new_family')"
                            class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                            @keydown.enter.prevent="addFamily"
                        />
                        <button type="button" class="rounded border border-gray-300 px-2 text-sm text-gray-600" @click="addFamily">
                            +
                        </button>
                    </div>
                    <p v-if="newFamilyError" class="mt-1 text-sm text-red-600">{{ newFamilyError }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.description') }}</label>

                    <ol v-if="form.description_tag_ids.length" class="mb-2 space-y-1">
                        <li
                            v-for="(id, index) in form.description_tag_ids"
                            :key="id"
                            class="flex items-center gap-2 rounded border border-gray-200 px-2 py-1 text-sm"
                        >
                            <span class="w-4 text-gray-400">{{ index + 1 }}</span>
                            <span class="flex-1 text-gray-700">{{ tagName(id) }}</span>
                            <button type="button" class="text-gray-400 disabled:opacity-30" :disabled="index === 0" @click="moveTagUp(index)">
                                ↑
                            </button>
                            <button
                                type="button"
                                class="text-gray-400 disabled:opacity-30"
                                :disabled="index === form.description_tag_ids.length - 1"
                                @click="moveTagDown(index)"
                            >
                                ↓
                            </button>
                            <button type="button" class="text-red-500" @click="removeSelectedTag(id)">×</button>
                        </li>
                    </ol>

                    <div class="relative">
                        <input
                            v-model="tagQuery"
                            type="text"
                            :placeholder="t('admin_projects.search_tag')"
                            class="w-full rounded border border-gray-300 px-3 py-2"
                        />
                        <ul
                            v-if="tagResults.length"
                            class="absolute z-10 mt-1 w-full rounded border border-gray-200 bg-white shadow"
                        >
                            <li
                                v-for="tag in tagResults"
                                :key="tag.id"
                                class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
                                @click="selectTag(tag)"
                            >
                                {{ tag.name }}
                            </li>
                        </ul>
                    </div>

                    <div class="mt-1 flex gap-2">
                        <input
                            v-model="newTagName"
                            type="text"
                            :placeholder="t('admin_projects.new_tag')"
                            class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                            @keydown.enter.prevent="addTag"
                        />
                        <button type="button" class="rounded border border-gray-300 px-2 text-sm text-gray-600" @click="addTag">
                            +
                        </button>
                    </div>
                    <p v-if="newTagError" class="mt-1 text-sm text-red-600">{{ newTagError }}</p>
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
