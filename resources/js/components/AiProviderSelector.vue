<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { BaseCard, BaseInput, BaseSelect } from '@/components/ui';
import { useToast } from '@/lib/toast';
import type { AiModelOption, AiProviderId, AiProviderOption } from '@/types/ai';

const toast = useToast();
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

const providerOptions = computed(() =>
    providers.value.map((provider) => ({
        value: provider.id,
        label: provider.label,
    })),
);
const modelOptions = computed(() => [
    { value: null, label: 'Por defecto' },
    ...filteredModels.value.map((model) => ({
        value: model.id,
        label: `${model.label}${
            model.free
                ? ' · gratis'
                : formatPrice(model.promptPrice)
                  ? ` · ${formatPrice(model.promptPrice)} entrada`
                  : ''
        }`,
    })),
]);
const selectedProvider = computed<string | number | null>({
    get: () => currentProvider.value,
    set: (provider) => {
        if (typeof provider !== 'string') {
            return;
        }

        currentProvider.value = provider as AiProviderId;
        void onProviderChange();
    },
});
const selectedModel = computed<string | number | null>({
    get: () => currentModel.value,
    set: (model) => {
        currentModel.value = typeof model === 'string' ? model : null;
        void onModelChange();
    },
});

onMounted(async () => {
    try {
        const [settingsResponse, providersResponse] = await Promise.all([
            fetch('/api/ai/settings', {
                headers: { Accept: 'application/json' },
            }),
            fetch('/api/ai/providers', {
                headers: { Accept: 'application/json' },
            }),
        ]);

        if (!settingsResponse.ok || !providersResponse.ok) {
            throw new Error('AI settings request failed');
        }

        providers.value = await providersResponse.json();
        const settings = await settingsResponse.json();
        currentProvider.value = settings.provider;
        currentModel.value = settings.model;

        await loadModels(currentProvider.value);
    } catch {
        toast.error('No se pudo cargar la configuración de IA.');
    }
});

async function loadModels(provider: AiProviderId) {
    loadingModels.value = true;
    modelFilter.value = '';

    try {
        const response = await fetch(`/api/ai/providers/${provider}/models`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('AI models request failed');
        }

        models.value = await response.json();
    } catch {
        models.value = [];
        toast.error('No se pudieron cargar los modelos disponibles.');
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
        const response = await fetch('/api/ai/settings', {
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

        if (!response.ok) {
            throw new Error('AI settings update failed');
        }
    } catch {
        toast.error('No se pudo guardar la configuración de IA.');
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
    <BaseCard
        variant="subtle"
        class="grid items-end gap-4 @sm/card:grid-cols-2 @xl/card:grid-cols-[minmax(12rem,0.7fr)_minmax(13rem,0.8fr)_minmax(16rem,1fr)_auto]"
    >
        <BaseSelect
            v-model="selectedProvider"
            label="Proveedor de IA"
            :options="providerOptions"
        />
        <BaseInput
            v-model="modelFilter"
            label="Filtrar modelos"
            placeholder="Nombre o identificador"
        />
        <BaseSelect
            v-model="selectedModel"
            label="Modelo"
            :options="modelOptions"
            :disabled="loadingModels"
        />
        <span
            class="min-h-11 self-end py-3 text-step-eyebrow font-medium text-ink-muted"
            role="status"
            aria-live="polite"
        >
            {{ saving ? 'Guardando configuración…' : 'Configuración local' }}
        </span>
    </BaseCard>
</template>
