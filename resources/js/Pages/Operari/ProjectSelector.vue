<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import OperariNav from '../../Components/OperariNav.vue';
import { api } from '../../api';

const { t } = useI18n();

const query = ref('');
const orderFabrications = ref([]);
const searching = ref(false);

let debounceHandle = null;

watch(query, (value) => {
    clearTimeout(debounceHandle);

    if (!value) {
        orderFabrications.value = [];
        return;
    }

    debounceHandle = setTimeout(async () => {
        searching.value = true;
        orderFabrications.value = await api.get(`/operari/api/order-fabrications?q=${encodeURIComponent(value)}`);
        searching.value = false;
    }, 250);
});

function selectOrderFabrication(orderFabrication) {
    router.visit(`/operari/order-fabrications/${orderFabrication.id}/equipment-list`);
}
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 py-10">
        <div class="mx-auto max-w-md space-y-4 rounded-lg bg-white p-6 shadow">
            <OperariNav />

            <h1 class="text-lg font-semibold text-gray-800">{{ t('selector.title') }}</h1>

            <div class="relative">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('selector.order_number') }}</label>
                <input v-model="query" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />

                <ul
                    v-if="orderFabrications.length"
                    class="absolute z-10 mt-1 w-full rounded border border-gray-200 bg-white shadow"
                >
                    <li
                        v-for="of in orderFabrications"
                        :key="of.id"
                        class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                        @click="selectOrderFabrication(of)"
                    >
                        <span class="font-medium">{{ of.number }}</span>
                        <span class="text-gray-500"> — {{ of.project.number }} ({{ of.project.family }})</span>
                    </li>
                </ul>
                <p v-else-if="query && !searching" class="mt-1 text-sm text-gray-400">
                    {{ t('selector.no_results') }}
                </p>
            </div>
        </div>
    </div>
</template>
