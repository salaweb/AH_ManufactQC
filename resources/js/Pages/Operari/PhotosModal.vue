<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { api } from '../../api';

const props = defineProps({
    open: { type: Boolean, default: false },
    equipmentId: { type: [Number, String], required: true },
});

const emit = defineEmits(['close', 'finished']);

const { t } = useI18n();

const files = ref([]);
const previews = ref([]);
const saving = ref(false);

function onFilesSelected(event) {
    const selected = Array.from(event.target.files ?? []).slice(0, 6);
    files.value = selected;
    previews.value = selected.map((file) => URL.createObjectURL(file));
}

async function finish() {
    saving.value = true;

    try {
        const formData = new FormData();
        files.value.forEach((file) => formData.append('photos[]', file));

        const equipment = await api.postForm(`/operari/api/equipment/${props.equipmentId}/photos`, formData);

        emit('finished', equipment);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm space-y-3 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-base font-semibold text-gray-800">{{ t('photos.title') }}</h2>
            <p class="text-sm text-gray-500">{{ t('photos.subtitle') }}</p>

            <input type="file" accept="image/*" multiple capture="environment" @change="onFilesSelected" />

            <div v-if="previews.length" class="grid grid-cols-3 gap-2">
                <img
                    v-for="(src, index) in previews"
                    :key="index"
                    :src="src"
                    class="h-20 w-full rounded object-cover"
                />
            </div>

            <div class="flex flex-col gap-2 pt-2">
                <button
                    type="button"
                    class="rounded bg-gray-800 py-2 text-sm font-medium text-white disabled:opacity-40"
                    :disabled="saving"
                    @click="finish"
                >
                    {{ files.length ? t('photos.save') : t('photos.skip') }}
                </button>
            </div>
        </div>
    </div>
</template>
