<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import StatCard from '../../Components/StatCard.vue';
import ChartDefects from '../../Components/ChartDefects.vue';
import PhotoGrid from '../../Components/PhotoGrid.vue';
import FilterBar from '../../Components/FilterBar.vue';
import { api } from '../../api';

const { t } = useI18n();

const projects = ref([]);
const filters = ref({ project_id: '', from: '', to: '' });

const stats = reactive({
    total_equipment: 0,
    checked_equipment: 0,
    completion_percentage: 0,
    total_defects: 0,
});
const defectsByType = ref([]);
const responsibilities = ref([]);
const trends = ref([]);
const recentPhotos = ref([]);

const defectsByTypeItems = computed(() => defectsByType.value.map((row) => ({ label: row.tipo, value: row.count })));
const responsibilitiesItems = computed(() =>
    responsibilities.value.map((row) => ({ label: row.responsibility, value: row.count })),
);
const trendsItems = computed(() => trends.value.map((row) => ({ label: row.section, value: row.defect_rate })));

async function loadProjects() {
    projects.value = await api.get('/api/projects');
}

async function loadDashboard() {
    const params = new URLSearchParams();
    if (filters.value.project_id) params.set('project_id', filters.value.project_id);
    if (filters.value.from) params.set('from', filters.value.from);
    if (filters.value.to) params.set('to', filters.value.to);

    const data = await api.get(`/api/dashboard?${params.toString()}`);

    Object.assign(stats, data.stats);
    defectsByType.value = data.defects_by_type;
    responsibilities.value = data.responsibilities;
    trends.value = data.trends;
    recentPhotos.value = data.recent_photos;
}

function logout() {
    router.post('/logout');
}

watch(filters, loadDashboard);

onMounted(() => {
    loadProjects();
    loadDashboard();
});
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('dashboard.title') }}</h1>
                <div class="flex items-center gap-4">
                    <Link href="/admin/projects" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ t('admin_projects.title') }}
                    </Link>
                    <button type="button" class="text-sm text-gray-500" @click="logout">
                        {{ t('auth.logout') }}
                    </button>
                </div>
            </div>

            <FilterBar v-model="filters" :projects="projects" />

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard :label="t('dashboard.total_equipment')" :value="stats.total_equipment" />
                <StatCard :label="t('dashboard.checked_equipment')" :value="stats.checked_equipment" />
                <StatCard :label="t('dashboard.completion_percentage')" :value="`${stats.completion_percentage}%`" />
                <StatCard :label="t('dashboard.total_defects')" :value="stats.total_defects" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <ChartDefects :title="t('dashboard.defects_by_type')" :items="defectsByTypeItems" />
                <ChartDefects :title="t('dashboard.responsibilities')" :items="responsibilitiesItems" />
                <ChartDefects :title="t('dashboard.trends')" :items="trendsItems" />
            </div>

            <PhotoGrid :photos="recentPhotos" />
        </div>
    </div>
</template>
