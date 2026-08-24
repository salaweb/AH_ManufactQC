<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    password: '',
});

function submit() {
    form.post('/operari/login');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <form class="w-full max-w-sm space-y-4 rounded-lg bg-white p-6 shadow" @submit.prevent="submit">
            <h1 class="text-lg font-semibold text-gray-800">Login Operari</h1>

            <div>
                <input v-model="form.username" type="text" placeholder="Usuari" class="w-full rounded border px-3 py-2" />
                <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
            </div>

            <div>
                <input v-model="form.password" type="password" placeholder="Password" class="w-full rounded border px-3 py-2" />
            </div>

            <button type="submit" class="w-full rounded bg-gray-800 py-2 text-white" :disabled="form.processing">
                Entrar
            </button>
        </form>
    </div>
</template>
