<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        class="flex min-h-screen items-center justify-center bg-[#FDFDFC] px-4 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <div
            class="w-full max-w-sm rounded-lg border border-gray-200 p-6 dark:border-gray-800"
        >
            <h1 class="mb-6 text-xl font-semibold">JobHunter</h1>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Email</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autofocus
                        autocomplete="username"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                    <span
                        v-if="form.errors.email"
                        class="text-xs text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.email }}
                    </span>
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400"
                        >Contraseña</span
                    >
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                    <span
                        v-if="form.errors.password"
                        class="text-xs text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.password }}
                    </span>
                </label>

                <label
                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                >
                    <input v-model="form.remember" type="checkbox" />
                    Recordarme
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                >
                    {{ form.processing ? 'Ingresando…' : 'Ingresar' }}
                </button>
            </form>
        </div>
    </div>
</template>
