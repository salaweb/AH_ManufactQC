<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const orderFabrications = ref([]);
const projects = ref([]);
const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { project_id: '', number: '' };
}

const form = reactive(blankForm());

async function load() {
    orderFabrications.value = await api.get('/api/order-fabrications');
    projects.value = await api.get('/api/projects');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(orderFabrication) {
    editingId.value = orderFabrication.id;
    errors.value = {};
    Object.assign(form, {
        project_id: orderFabrication.project_id,
        number: orderFabrication.number,
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
            await api.put(`/api/order-fabrications/${editingId.value}`, form);
        } else {
            await api.post('/api/order-fabrications', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(orderFabrication) {
    if (!confirm(t('admin_order_fabrications.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/order-fabrications/${orderFabrication.id}`);
    await load();
}

function manageEquipment(orderFabrication) {
    router.visit(`/admin/order-fabrications/${orderFabrication.id}/equipment`);
}

function projectNumber(id) {
    return projects.value.find((project) => project.id === id)?.number ?? '';
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_order_fabrications.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_order_fabrications.add') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_order_fabrications.number') }}</th>
                            <th class="px-4 py-2">{{ t('admin_order_fabrications.project') }}</th>
                            <th class="px-4 py-2">{{ t('admin_order_fabrications.description') }}</th>
                            <th class="px-4 py-2">{{ t('admin_order_fabrications.equipment_count') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="orderFabrication in orderFabrications" :key="orderFabrication.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ orderFabrication.number }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ orderFabrication.project?.number }}</td>
                            <td
                                class="max-w-xs truncate px-4 py-2 text-gray-600"
                                :title="orderFabrication.project?.sections.map((s) => s.name).join(' ')"
                            >
                                {{ orderFabrication.project?.sections.map((s) => s.name).join(' ') }}
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ orderFabrication.equipment_count ?? 0 }}</td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <Button variant="ghost" @click="manageEquipment(orderFabrication)">
                                    {{ t('admin_order_fabrications.manage_equipment') }}
                                </Button>
                                <Button variant="ghost" @click="openEdit(orderFabrication)">
                                    {{ t('admin_order_fabrications.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(orderFabrication)">
                                    {{ t('admin_order_fabrications.delete') }}
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
                    {{ editingId ? t('admin_order_fabrications.edit') : t('admin_order_fabrications.add') }}
                </h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_order_fabrications.project') }}</label>
                    <select v-model="form.project_id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="" disabled>—</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.number }}
                        </option>
                    </select>
                    <p v-if="errors.project_id" class="mt-1 text-sm text-red-600">{{ errors.project_id[0] }}</p>
                </div>

                <FormField
                    v-model="form.number"
                    :label="t('admin_order_fabrications.number')"
                    :error="errors.number?.[0]"
                />

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_order_fabrications.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_order_fabrications.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
