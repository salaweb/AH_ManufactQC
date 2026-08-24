<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import { api } from '../../api';

const { t } = useI18n();

const query = ref('');
const projects = ref([]);
const selectedProject = ref(null);

const orderFabrications = ref([]);
const selectedOrderFabricationId = ref('');

let debounceHandle = null;
let skipNextSearch = false;

watch(query, (value) => {
    if (skipNextSearch) {
        skipNextSearch = false;
        return;
    }

    selectedProject.value = null;
    orderFabrications.value = [];
    selectedOrderFabricationId.value = '';

    clearTimeout(debounceHandle);

    if (!value) {
        projects.value = [];
        return;
    }

    debounceHandle = setTimeout(async () => {
        projects.value = await api.get(`/operari/api/projects?q=${encodeURIComponent(value)}`);
    }, 250);
});

async function selectProject(project) {
    selectedProject.value = project;
    skipNextSearch = true;
    query.value = project.number;
    projects.value = [];
    orderFabrications.value = await api.get(`/operari/api/projects/${project.id}/order-fabrications`);
}

function goToEquipmentList() {
    if (!selectedOrderFabricationId.value) {
        return;
    }

    router.visit(`/operari/order-fabrications/${selectedOrderFabricationId.value}/equipment-list`);
}
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
            <h1 class="text-lg font-semibold text-gray-800">{{ t('selector.title') }}</h1>

            <div class="relative">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('selector.project_number') }}</label>
                <input v-model="query" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />

                <ul
                    v-if="projects.length"
                    class="absolute z-10 mt-1 w-full rounded border border-gray-200 bg-white shadow"
                >
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                        @click="selectProject(project)"
                    >
                        {{ project.number }} — {{ project.family }}
                    </li>
                </ul>
                <p v-else-if="query && !selectedProject" class="mt-1 text-sm text-gray-400">
                    {{ t('selector.no_results') }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('selector.order_number') }}</label>
                <select
                    v-model="selectedOrderFabricationId"
                    class="w-full rounded border border-gray-300 px-3 py-2"
                    :disabled="!selectedProject"
                >
                    <option value="" disabled>
                        {{ selectedProject ? '' : t('selector.project_required') }}
                    </option>
                    <option v-for="of in orderFabrications" :key="of.id" :value="of.id">
                        {{ of.number }}
                    </option>
                </select>
            </div>

            <button
                type="button"
                class="w-full rounded bg-gray-800 py-2 text-white disabled:opacity-40"
                :disabled="!selectedOrderFabricationId"
                @click="goToEquipmentList"
            >
                {{ t('selector.start') }}
            </button>
        </div>
    </div>
</template>
