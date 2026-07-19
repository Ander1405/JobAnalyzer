<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { formatCost, formatDuration } from '@/lib/utils';
import type { ProfileUploadResponse, ProfileUsage } from '@/types/profile';

const content = ref('');
const expanded = ref(false);
const uploading = ref(false);
const error = ref<string | null>(null);
const lastUsage = ref<{ model: string; usage: ProfileUsage } | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

onMounted(async () => {
    const response = await fetch('/api/profile', {
        headers: { Accept: 'application/json' },
    });
    const data = await response.json();
    content.value = data.content;
});

async function upload() {
    const file = fileInput.value?.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('resume', file);

    try {
        const response = await fetch('/api/profile/upload', {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            error.value =
                data.message ?? 'No se pudo procesar la hoja de vida.';

            return;
        }

        const upload = data as ProfileUploadResponse;
        content.value = upload.content;
        lastUsage.value = { model: upload.model, usage: upload.usage };
        expanded.value = true;
    } catch {
        error.value = 'No se pudo conectar con el servidor.';
    } finally {
        uploading.value = false;

        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
}
</script>

<template>
    <div
        class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900"
    >
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm text-gray-600 dark:text-gray-400"
                >Perfil (hoja de vida)</span
            >

            <input
                ref="fileInput"
                type="file"
                accept="application/pdf"
                class="max-w-56 text-xs"
            />

            <button
                type="button"
                :disabled="uploading"
                class="rounded-md bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                @click="upload"
            >
                {{ uploading ? 'Subiendo…' : 'Subir CV' }}
            </button>

            <button
                v-if="content"
                type="button"
                class="text-xs text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
                @click="expanded = !expanded"
            >
                {{ expanded ? 'Ocultar perfil' : 'Ver perfil actual' }}
            </button>
        </div>

        <p v-if="error" class="text-xs text-red-600 dark:text-red-400">
            {{ error }}
        </p>

        <p v-if="lastUsage" class="text-xs text-gray-500 dark:text-gray-400">
            ✅ Perfil actualizado · {{ lastUsage.model }} ·
            {{ formatDuration(lastUsage.usage.durationMs) }} ·
            {{
                (lastUsage.usage.inputTokens ?? 0) +
                (lastUsage.usage.outputTokens ?? 0)
            }}
            tokens ·
            {{ formatCost(lastUsage.usage.costUsd) }}
        </p>

        <pre
            v-if="expanded && content"
            class="max-h-64 overflow-y-auto rounded-md bg-white p-3 text-xs whitespace-pre-wrap text-gray-700 dark:bg-gray-950 dark:text-gray-300"
            >{{ content }}</pre>
    </div>
</template>
