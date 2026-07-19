<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import type { AiModelOption, AiProviderId, AiProviderOption } from '@/types/ai';

const providers = ref<AiProviderOption[]>([]);
const models = ref<AiModelOption[]>([]);
const currentProvider = ref<AiProviderId>('claude_cli');
const currentModel = ref<string | null>(null);
const modelFilter = ref('');
const loadingModels = ref(false);
const saving = ref(false);

const filteredModels = computed(() => {
    const term = modelFilter.value.trim().toLowerCase();

    if (!term) {
        return models.value;
    }

    return models.value.filter(
        (model) =>
            model.label.toLowerCase().includes(term) ||
            model.id.toLowerCase().includes(term),
    );
});

onMounted(async () => {
    const [settingsResponse, providersResponse] = await Promise.all([
        fetch('/api/ai/settings', { headers: { Accept: 'application/json' } }),
        fetch('/api/ai/providers', { headers: { Accept: 'application/json' } }),
    ]);

    providers.value = await providersResponse.json();
    const settings = await settingsResponse.json();
    currentProvider.value = settings.provider;
    currentModel.value = settings.model;

    await loadModels(currentProvider.value);
});

async function loadModels(provider: AiProviderId) {
    loadingModels.value = true;
    modelFilter.value = '';

    try {
        const response = await fetch(`/api/ai/providers/${provider}/models`, {
            headers: { Accept: 'application/json' },
        });
        models.value = await response.json();
    } finally {
        loadingModels.value = false;
    }
}

async function onProviderChange() {
    currentModel.value = null;
    await loadModels(currentProvider.value);
    await save();
}

async function onModelChange() {
    await save();
}

async function save() {
    saving.value = true;

    try {
        await fetch('/api/ai/settings', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                provider: currentProvider.value,
                model: currentModel.value,
            }),
        });
    } finally {
        saving.value = false;
    }
}

function formatPrice(pricePerToken: number | null): string | null {
    if (pricePerToken === null || pricePerToken < 0) {
        return null;
    }

    if (pricePerToken === 0) {
        return null;
    }

    return `$${(pricePerToken * 1_000_000).toFixed(2)}/1M`;
}
</script>

<template>
    <div
        class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900"
    >
        <label class="flex flex-col gap-1 text-sm">
            <span class="text-gray-600 dark:text-gray-400"
                >Proveedor de IA</span
            >
            <select
                v-model="currentProvider"
                class="rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-950"
                @change="onProviderChange"
            >
                <option
                    v-for="provider in providers"
                    :key="provider.id"
                    :value="provider.id"
                >
                    {{ provider.label }}
                </option>
            </select>
        </label>

        <label class="flex flex-col gap-1 text-sm">
            <span class="text-gray-600 dark:text-gray-400">Modelo</span>
            <input
                v-model="modelFilter"
                type="text"
                placeholder="Filtrar modelos…"
                class="w-48 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-950"
            />
            <select
                v-model="currentModel"
                :disabled="loadingModels"
                class="w-64 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-950"
                @change="onModelChange"
            >
                <option :value="null">Por defecto</option>
                <option
                    v-for="model in filteredModels"
                    :key="model.id"
                    :value="model.id"
                >
                    {{ model.label
                    }}{{
                        model.free
                            ? ' · gratis'
                            : formatPrice(model.promptPrice)
                              ? ` · ${formatPrice(model.promptPrice)} in`
                              : ''
                    }}
                </option>
            </select>
        </label>

        <span v-if="saving" class="text-xs text-gray-500 dark:text-gray-400"
            >Guardando…</span
        >
    </div>
</template>
