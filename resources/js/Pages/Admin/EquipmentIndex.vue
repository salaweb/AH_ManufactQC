<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const props = defineProps({
    orderFabricationId: { type: [Number, String], required: true },
});

const { t } = useI18n();

const orderFabrication = ref(null);
const equipment = ref([]);

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

const GENERATE_MAX_QUANTITY = 500;

const generateOpen = ref(false);
const generateErrors = ref({});
const generating = ref(false);
const generateCancelled = ref(false);
const generateResult = ref('');
const generateProgress = reactive({ done: 0, total: 0 });

function blankForm() {
    return { serie_number: '', observations: '' };
}

function blankGenerateForm() {
    return { start: '', quantity: '' };
}

const form = reactive(blankForm());
const generateForm = reactive(blankGenerateForm());

async function load() {
    orderFabrication.value = await api.get(`/api/order-fabrications/${props.orderFabricationId}`);
    equipment.value = await api.get(`/api/equipment?order_fabrication_id=${props.orderFabricationId}`);
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(item) {
    editingId.value = item.id;
    errors.value = {};
    Object.assign(form, {
        serie_number: item.serie_number,
        observations: item.observations ?? '',
    });
    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
}

async function save() {
    saving.value = true;
    errors.value = {};

    const payload = {
        ...form,
        project_id: orderFabrication.value.project_id,
        order_fabrication_id: props.orderFabricationId,
    };

    try {
        if (editingId.value) {
            await api.put(`/api/equipment/${editingId.value}`, payload);
        } else {
            await api.post('/api/equipment', payload);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(item) {
    if (!confirm(t('admin_equipment.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/equipment/${item.id}`);
    await load();
}

function nextSerial(serial) {
    const match = serial.match(/(\d+)$/);

    if (!match) {
        return null;
    }

    const digits = match[1];
    const prefix = serial.slice(0, serial.length - digits.length);
    const next = (BigInt(digits) + 1n).toString().padStart(digits.length, '0');

    return prefix + next;
}

function openGenerate() {
    generateErrors.value = {};
    generateResult.value = '';
    Object.assign(generateForm, blankGenerateForm());
    generateOpen.value = true;
}

function closeGenerate() {
    if (generating.value) {
        generateCancelled.value = true;
        return;
    }

    generateOpen.value = false;
}

async function generate() {
    generateErrors.value = {};
    generateResult.value = '';

    const quantity = parseInt(generateForm.quantity, 10);

    if (quantity > GENERATE_MAX_QUANTITY) {
        generateErrors.value = {
            quantity: [t('admin_equipment.generate_max_hint', { max: GENERATE_MAX_QUANTITY })],
        };
        return;
    }

    const serials = [generateForm.start];

    for (let i = 1; i < quantity; i++) {
        const next = nextSerial(serials[serials.length - 1]);

        if (next === null) {
            generateErrors.value = { start: [t('admin_equipment.generate_error_hint')] };
            return;
        }

        serials.push(next);
    }

    generating.value = true;
    generateCancelled.value = false;
    generateProgress.done = 0;
    generateProgress.total = serials.length;

    let created = 0;

    for (const serial of serials) {
        if (generateCancelled.value) {
            break;
        }

        try {
            await api.post('/api/equipment', {
                project_id: orderFabrication.value.project_id,
                order_fabrication_id: props.orderFabricationId,
                serie_number: serial,
                observations: '',
            });
            created++;
        } catch (error) {
            // Keep going so a single duplicate doesn't block the rest of the batch.
        }

        generateProgress.done++;
    }

    generateResult.value = t('admin_equipment.generate_result', { created, total: generateProgress.done });
    await load();
    generating.value = false;
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <Link href="/admin/order-fabrications" class="text-sm text-gray-500 hover:text-gray-700">
                ← {{ t('common.back') }}
            </Link>

            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ t('admin_equipment.title') }} — {{ orderFabrication?.number }}
                    <span class="text-sm font-normal text-gray-500">({{ orderFabrication?.project?.number }})</span>
                </h1>
                <div class="flex gap-2">
                    <Button variant="outline" @click="openGenerate">
                        {{ t('admin_equipment.generate_title') }}
                    </Button>
                    <Button @click="openCreate">
                        {{ t('admin_equipment.add') }}
                    </Button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_equipment.serie_number') }}</th>
                            <th class="px-4 py-2">{{ t('admin_equipment.status') }}</th>
                            <th class="px-4 py-2">{{ t('admin_equipment.observations') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in equipment" :key="item.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ item.serie_number }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ t(`status.${item.status}`) }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ item.observations }}</td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <Button variant="ghost" @click="openEdit(item)">
                                    {{ t('admin_equipment.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(item)">
                                    {{ t('admin_equipment.delete') }}
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
                    {{ editingId ? t('admin_equipment.edit') : t('admin_equipment.add') }}
                </h2>

                <FormField
                    v-model="form.serie_number"
                    :label="t('admin_equipment.serie_number')"
                    :error="errors.serie_number?.[0]"
                />

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_equipment.observations') }}</label>
                    <textarea v-model="form.observations" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_equipment.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_equipment.save') }}
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="generateOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md space-y-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">{{ t('admin_equipment.generate_title') }}</h2>

                <FormField
                    v-model="generateForm.start"
                    :label="t('admin_equipment.generate_start')"
                    :error="generateErrors.start?.[0]"
                />

                <FormField
                    v-model="generateForm.quantity"
                    type="number"
                    :label="t('admin_equipment.generate_quantity')"
                    :error="generateErrors.quantity?.[0]"
                />

                <p v-if="generating" class="text-sm text-gray-600">
                    {{ t('admin_equipment.generate_progress', { done: generateProgress.done, total: generateProgress.total }) }}
                </p>
                <p v-else-if="generateResult" class="text-sm text-gray-600">{{ generateResult }}</p>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeGenerate">
                        {{ generating ? t('admin_equipment.generate_stop') : t('admin_equipment.cancel') }}
                    </Button>
                    <Button
                        :disabled="generating || !generateForm.start || !generateForm.quantity"
                        @click="generate"
                    >
                        {{ t('admin_equipment.generate_button') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
