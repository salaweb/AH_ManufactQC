<script setup>
import { reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { api } from '../../api';

const props = defineProps({
    open: { type: Boolean, default: false },
    equipmentId: { type: [Number, String], required: true },
    answerId: { type: [Number, String], default: null },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();

const types = ['visual', 'dimensional', 'funcional'];
const responsibilities = ['produccio', 'proveidor', 'disseny'];

function blankForm() {
    return {
        tipo: types[0],
        observation: '',
        responsibility: responsibilities[0],
        actions: '',
    };
}

const form = reactive(blankForm());
const saving = reactive({ value: false });

async function save(keepOpen) {
    saving.value = true;

    try {
        const defect = await api.post('/operari/api/defects', {
            equipment_id: props.equipmentId,
            answer_id: props.answerId,
            ...form,
        });

        emit('saved', defect);

        if (keepOpen) {
            Object.assign(form, blankForm());
        } else {
            emit('close');
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm space-y-3 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-base font-semibold text-gray-800">{{ t('defect.title') }}</h2>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.type') }}</label>
                <select v-model="form.tipo" class="w-full rounded border border-gray-300 px-3 py-2">
                    <option v-for="type in types" :key="type" :value="type">{{ t(`defect.type_${type}`) }}</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.observation') }}</label>
                <textarea v-model="form.observation" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.responsibility') }}</label>
                <select v-model="form.responsibility" class="w-full rounded border border-gray-300 px-3 py-2">
                    <option v-for="item in responsibilities" :key="item" :value="item">
                        {{ t(`defect.responsibility_${item}`) }}
                    </option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.actions') }}</label>
                <textarea v-model="form.actions" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
            </div>

            <div class="flex flex-col gap-2 pt-2">
                <button
                    type="button"
                    class="rounded bg-red-600 py-2 text-sm font-medium text-white disabled:opacity-40"
                    :disabled="saving.value"
                    @click="save(false)"
                >
                    {{ t('defect.save') }}
                </button>
                <button
                    type="button"
                    class="rounded border border-gray-300 py-2 text-sm font-medium text-gray-700 disabled:opacity-40"
                    :disabled="saving.value"
                    @click="save(true)"
                >
                    {{ t('defect.add_another') }}
                </button>
                <button type="button" class="text-sm text-gray-500" @click="emit('close')">
                    {{ t('defect.cancel') }}
                </button>
            </div>
        </div>
    </div>
</template>
