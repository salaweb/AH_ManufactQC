<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const projects = ref([]);
const sections = ref([]);
const families = ref([]);

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

const newFamilyName = ref('');
const newSectionName = ref('');
const newFamilyError = ref('');
const newSectionError = ref('');
const sectionQuery = ref('');

const sectionResults = computed(() => {
    const query = sectionQuery.value.trim().toLowerCase();

    if (!query) {
        return [];
    }

    return sections.value
        .filter((section) => !form.section_ids.includes(section.id))
        .filter((section) => section.name.toLowerCase().includes(query))
        .slice(0, 8);
});

function sectionName(id) {
    return sections.value.find((section) => section.id === id)?.name ?? '';
}

function selectSection(section) {
    form.section_ids.push(section.id);
    sectionQuery.value = '';
}

function removeSelectedSection(id) {
    form.section_ids = form.section_ids.filter((sectionId) => sectionId !== id);
}

function moveSectionUp(index) {
    if (index === 0) {
        return;
    }

    const ids = form.section_ids;
    [ids[index - 1], ids[index]] = [ids[index], ids[index - 1]];
}

function moveSectionDown(index) {
    const ids = form.section_ids;

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
    };
}

const form = reactive(blankForm());

async function load() {
    projects.value = await api.get('/api/projects');
    sections.value = await api.get('/api/sections');
    families.value = await api.get('/api/families');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    newFamilyName.value = '';
    newSectionName.value = '';
    newFamilyError.value = '';
    newSectionError.value = '';
    sectionQuery.value = '';
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(project) {
    editingId.value = project.id;
    errors.value = {};
    newFamilyName.value = '';
    newSectionName.value = '';
    newFamilyError.value = '';
    newSectionError.value = '';
    sectionQuery.value = '';
    Object.assign(form, {
        number: project.number,
        family_id: project.family_id,
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

async function addSection() {
    if (!newSectionName.value.trim()) {
        return;
    }

    newSectionError.value = '';

    try {
        const section = await api.post('/api/sections', { name: newSectionName.value.trim() });
        sections.value.push(section);
        sections.value.sort((a, b) => a.name.localeCompare(b.name));
        form.section_ids.push(section.id);
        newSectionName.value = '';
    } catch (error) {
        newSectionError.value = error.data?.errors?.name?.[0] ?? t('admin_projects.add_failed');
    }
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_projects.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_projects.add') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_projects.number') }}</th>
                            <th class="px-4 py-2">{{ t('admin_projects.family') }}</th>
                            <th class="px-4 py-2">{{ t('admin_projects.description') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in projects" :key="project.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ project.number }}</td>
                            <td class="px-4 py-2">{{ project.family?.name }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                {{ project.sections.map((s) => s.name).join(' ') }}
                            </td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <Button variant="ghost" @click="openEdit(project)">
                                    {{ t('admin_projects.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(project)">
                                    {{ t('admin_projects.delete') }}
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
                        <Button variant="outline" @click="addFamily">
                            +
                        </Button>
                    </div>
                    <p v-if="newFamilyError" class="mt-1 text-sm text-red-600">{{ newFamilyError }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.description') }}</label>

                    <ol v-if="form.section_ids.length" class="mb-2 space-y-1">
                        <li
                            v-for="(id, index) in form.section_ids"
                            :key="id"
                            class="flex items-center gap-2 rounded border border-gray-200 px-2 py-1 text-sm"
                        >
                            <span class="w-4 text-gray-400">{{ index + 1 }}</span>
                            <span class="flex-1 text-gray-700">{{ sectionName(id) }}</span>
                            <Button
                                variant="ghost"
                                :disabled="index === 0"
                                @click="moveSectionUp(index)"
                            >
                                ↑
                            </Button>
                            <Button
                                variant="ghost"
                                :disabled="index === form.section_ids.length - 1"
                                @click="moveSectionDown(index)"
                            >
                                ↓
                            </Button>
                            <Button variant="ghost-danger" @click="removeSelectedSection(id)">×</Button>
                        </li>
                    </ol>

                    <div class="relative">
                        <input
                            v-model="sectionQuery"
                            type="text"
                            :placeholder="t('admin_projects.search_section')"
                            class="w-full rounded border border-gray-300 px-3 py-2"
                        />
                        <ul
                            v-if="sectionResults.length"
                            class="absolute z-10 mt-1 w-full rounded border border-gray-200 bg-white shadow"
                        >
                            <li
                                v-for="section in sectionResults"
                                :key="section.id"
                                class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
                                @click="selectSection(section)"
                            >
                                {{ section.name }}
                            </li>
                        </ul>
                    </div>

                    <div class="mt-1 flex gap-2">
                        <input
                            v-model="newSectionName"
                            type="text"
                            :placeholder="t('admin_projects.new_section')"
                            class="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                            @keydown.enter.prevent="addSection"
                        />
                        <Button variant="outline" @click="addSection">
                            +
                        </Button>
                    </div>
                    <p v-if="newSectionError" class="mt-1 text-sm text-red-600">{{ newSectionError }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_projects.observations') }}</label>
                    <textarea v-model="form.observations" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_projects.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_projects.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
