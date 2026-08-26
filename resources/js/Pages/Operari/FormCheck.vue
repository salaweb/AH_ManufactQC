<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import OperariNav from '../../Components/OperariNav.vue';
import ButtonGroup from '../../Components/ButtonGroup.vue';
import Button from '../../Components/Button.vue';
import DefectModal from './DefectModal.vue';
import PhotosModal from './PhotosModal.vue';
import { api } from '../../api';

const props = defineProps({
    equipmentId: { type: [Number, String], required: true },
});

const { t, locale } = useI18n();

const categoryOrder = ['estetica', 'funcional_mecanica', 'electronica'];

function questionsByCategory(section) {
    return categoryOrder
        .map((category) => ({
            category,
            questions: section.questions.filter((question) => question.category === category),
        }))
        .filter((group) => group.questions.length > 0);
}

const equipment = ref(null);
const project = ref(null);
const sections = ref([]);
const answers = reactive({});
const savedAnswerIds = reactive({});
const observations = ref('');

const defectModal = reactive({ open: false, questionId: null, answerId: null });
const photosModal = reactive({ open: false });

let observationsTimer = null;

async function load() {
    const data = await api.get(`/operari/api/equipment/${props.equipmentId}`);

    equipment.value = data.equipment;
    project.value = data.equipment.project;
    sections.value = data.sections;
    observations.value = data.equipment.observations ?? '';

    for (const section of data.sections) {
        for (const question of section.questions) {
            if (question.answer) {
                answers[question.id] = question.answer.response;
            }
        }
    }
}

async function answerQuestion(question, response) {
    answers[question.id] = response;

    const answer = await api.post('/operari/api/answers', {
        equipment_id: props.equipmentId,
        question_id: question.id,
        response,
        language_chosen: locale.value,
    });

    savedAnswerIds[question.id] = answer.id;

    if (response === 'defect') {
        defectModal.questionId = question.id;
        defectModal.answerId = answer.id;
        defectModal.open = true;
    }
}

function saveObservations() {
    clearTimeout(observationsTimer);
    observationsTimer = setTimeout(() => {
        api.patch(`/operari/api/equipment/${props.equipmentId}`, {
            observations: observations.value,
        });
    }, 500);
}

function openFinish() {
    photosModal.open = true;
}

function onFinished() {
    router.visit(`/operari/order-fabrications/${equipment.value.order_fabrication_id}/equipment-list`);
}

onMounted(load);
</script>

<template>
    <LanguageSelector />

    <div v-if="equipment" class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-xl space-y-6 rounded-lg bg-white p-6 shadow">
            <OperariNav :back-href="`/operari/order-fabrications/${equipment.order_fabrication_id}/equipment-list`" />

            <div>
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ project.sections.map((s) => s.name).join(' ') }}
                </h1>
                <p v-if="project.observations" class="mt-1 text-sm text-gray-500">
                    {{ t('form.global_observations') }}: {{ project.observations }}
                </p>
                <p class="mt-1 text-sm font-medium text-gray-600">{{ equipment.serie_number }}</p>
            </div>

            <div v-for="section in sections" :key="section.id" class="space-y-4">
                <h2 class="border-b pb-1 text-sm font-semibold uppercase text-gray-500">{{ section.name }}</h2>

                <div v-for="group in questionsByCategory(section)" :key="group.category" class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase text-gray-400">{{ t(`category.${group.category}`) }}</h3>

                    <div v-for="question in group.questions" :key="question.id" class="space-y-2">
                        <p class="text-sm text-gray-700">{{ question.text }}</p>
                        <ButtonGroup
                            :model-value="answers[question.id] ?? null"
                            @update:model-value="(value) => answerQuestion(question, value)"
                        />
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    {{ t('form.equipment_observations') }}
                </label>
                <textarea
                    v-model="observations"
                    :placeholder="t('form.equipment_observations_placeholder')"
                    class="w-full rounded border border-gray-300 px-3 py-2"
                    rows="3"
                    @input="saveObservations"
                />
            </div>

            <Button class="w-full" @click="openFinish">
                {{ t('form.finish') }}
            </Button>
        </div>
    </div>

    <DefectModal
        :open="defectModal.open"
        :equipment-id="equipmentId"
        :answer-id="defectModal.answerId"
        @close="defectModal.open = false"
        @saved="() => {}"
    />

    <PhotosModal
        :open="photosModal.open"
        :equipment-id="equipmentId"
        @finished="onFinished"
    />
</template>
