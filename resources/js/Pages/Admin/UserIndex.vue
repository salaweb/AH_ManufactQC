<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const roles = ['admin', 'qc', 'operari'];

const users = ref([]);
const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { name: '', role: 'operari', email: '', username: '', password: '' };
}

const form = reactive(blankForm());

const showEmail = computed(() => form.role === 'admin' || form.role === 'qc');
const showUsername = computed(() => form.role === 'operari');

async function load() {
    users.value = await api.get('/api/users');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(user) {
    editingId.value = user.id;
    errors.value = {};
    Object.assign(form, {
        name: user.name,
        role: user.role,
        email: user.email ?? '',
        username: user.username ?? '',
        password: '',
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
            await api.put(`/api/users/${editingId.value}`, form);
        } else {
            await api.post('/api/users', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(user) {
    if (!confirm(t('admin_users.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/users/${user.id}`);
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
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_users.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_users.add') }}
                </Button>
            </div>

            <div class="rounded-lg bg-white shadow">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2">{{ t('admin_users.name') }}</th>
                            <th class="px-4 py-2">{{ t('admin_users.role') }}</th>
                            <th class="px-4 py-2">{{ t('admin_users.email') }} / {{ t('admin_users.username') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ t(`admin_users.role_${user.role}`) }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ user.email ?? user.username }}</td>
                            <td class="space-x-2 px-4 py-2 text-right">
                                <Button variant="ghost" @click="openEdit(user)">
                                    {{ t('admin_users.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(user)">
                                    {{ t('admin_users.delete') }}
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
                    {{ editingId ? t('admin_users.edit') : t('admin_users.add') }}
                </h2>

                <FormField v-model="form.name" :label="t('admin_users.name')" :error="errors.name?.[0]" />

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_users.role') }}</label>
                    <select v-model="form.role" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ t(`admin_users.role_${role}`) }}
                        </option>
                    </select>
                </div>

                <FormField
                    v-if="showEmail"
                    v-model="form.email"
                    type="email"
                    :label="t('admin_users.email')"
                    :error="errors.email?.[0]"
                />

                <FormField
                    v-if="showUsername"
                    v-model="form.username"
                    :label="t('admin_users.username')"
                    :error="errors.username?.[0]"
                />

                <div>
                    <FormField
                        v-model="form.password"
                        type="password"
                        :label="t('admin_users.password')"
                        :error="errors.password?.[0]"
                    />
                    <p v-if="editingId" class="mt-1 text-sm text-gray-500">{{ t('admin_users.password_hint') }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_users.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_users.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
