<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { importMethod as importCv } from '@/actions/App/Http/Controllers/Api/ProfileController';
import {
    apply as applySuggestions,
    review as reviewWithAi,
} from '@/actions/App/Http/Controllers/Api/ProfileReviewController';
import {
    activate,
    index as profilesIndex,
    store as storeVariant,
    sync as syncVariant,
    update as updateVariant,
} from '@/actions/App/Http/Controllers/Api/ProfileVariantController';
import AppIcon from '@/components/AppIcon.vue';
import DiffViewer from '@/components/DiffViewer.vue';
import {
    BaseButton,
    BaseCard,
    BaseInput,
    BaseSelect,
    BaseSkeleton,
    BaseTabs,
    BaseTag,
    BaseTextarea,
    EmptyState,
    MatchScore,
} from '@/components/ui';
import { useToast } from '@/lib/toast';
import { formatCost, formatDuration } from '@/lib/utils';
import type {
    AtsAnalysis,
    Profile,
    ProfileReviewUsage,
    ProfileSuggestion,
    ProfileSuggestionAction,
    ProfileSuggestionField,
} from '@/types/profile';

type TabKey = 'personal' | 'cv' | 'ats' | 'security';

const TABS: { value: TabKey; label: string }[] = [
    { value: 'personal', label: 'Datos' },
    { value: 'cv', label: 'Mi CV' },
    { value: 'ats', label: 'ATS' },
    { value: 'security', label: 'Seguridad' },
];

const activeTab = ref<TabKey>('personal');

const toast = useToast();

const FIELD_LABELS: Record<ProfileSuggestionField, string> = {
    headline: 'Titular',
    summary: 'Resumen',
    english_level: 'Nivel de inglés',
    experience: 'Experiencia',
    skills: 'Skills',
    education: 'Educación',
    certifications: 'Certificaciones',
    languages: 'Idiomas',
};

const ACTION_LABELS: Record<ProfileSuggestionAction, string> = {
    replace: 'Reemplazar',
    add: 'Agregar',
    remove: 'Eliminar',
};

const ENGLISH_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

const profiles = ref<Profile[]>([]);
const selectedSlug = ref<string | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const saving = ref(false);
const syncing = ref(false);

const uploading = ref(false);
const uploadError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const newVariantOpen = ref(false);
const newVariantSlug = ref('');
const newVariantLabel = ref('');
const newVariantError = ref<string | null>(null);
const creatingVariant = ref(false);

const rawMarkdown = ref('');

const reviewing = ref(false);
const reviewError = ref<string | null>(null);
const reviewUsage = ref<ProfileReviewUsage | null>(null);
const suggestions = ref<ProfileSuggestion[]>([]);
const approved = ref<Record<string, boolean>>({});
const applying = ref(false);
const applyError = ref<string | null>(null);

type EditableForm = {
    label: string;
    headline: string;
    summary: string;
    experience: string[];
    skills: string[];
    education: string[];
    languages: string[];
    englishLevel: string;
    certifications: string[];
};

const form = ref<EditableForm>(emptyForm());

function emptyForm(): EditableForm {
    return {
        label: '',
        headline: '',
        summary: '',
        experience: [],
        skills: [],
        education: [],
        languages: [],
        englishLevel: '',
        certifications: [],
    };
}

const selected = computed(
    () =>
        profiles.value.find((profile) => profile.slug === selectedSlug.value) ??
        null,
);

const profileOptions = computed(() =>
    profiles.value.map((profile) => ({
        value: profile.slug,
        label: `${profile.label} (${profile.slug})${profile.is_active ? ' · activa' : ''}`,
    })),
);

const selectedProfileSlug = computed<string | number | null>({
    get: () => selectedSlug.value,
    set: (slug) => selectProfile(typeof slug === 'string' ? slug : null),
});

const englishLevelOptions = [
    { value: '', label: 'No especificado' },
    ...ENGLISH_LEVELS.map((level) => ({ value: level, label: level })),
];

const corrections = computed(() =>
    suggestions.value.filter(
        (suggestion) => suggestion.category === 'correction',
    ),
);
const improvements = computed(() =>
    suggestions.value.filter(
        (suggestion) => suggestion.category === 'improvement',
    ),
);
const approvedSuggestions = computed(() =>
    suggestions.value.filter((suggestion) => approved.value[suggestion.id]),
);

onMounted(loadProfiles);

async function loadProfiles(selectSlug?: string) {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(profilesIndex.url(), {
            headers: { Accept: 'application/json' },
        });
        profiles.value = await response.json();

        const active = profiles.value.find((profile) => profile.is_active);
        const preferred =
            selectSlug ??
            selectedSlug.value ??
            active?.slug ??
            profiles.value[0]?.slug ??
            null;

        selectProfile(preferred);
    } catch {
        toast.error('No se pudieron cargar los perfiles.');
    } finally {
        loading.value = false;
    }
}

function selectProfile(slug: string | null) {
    selectedSlug.value = slug;
    const profile = profiles.value.find((item) => item.slug === slug) ?? null;

    reviewError.value = null;
    reviewUsage.value = null;
    suggestions.value = [];
    approved.value = {};
    applyError.value = null;

    if (!profile) {
        form.value = emptyForm();
        rawMarkdown.value = '';

        return;
    }

    form.value = {
        label: profile.label,
        headline: profile.headline ?? '',
        summary: profile.summary ?? '',
        experience: [...(profile.experience ?? [])],
        skills: [...(profile.skills ?? [])],
        education: [...(profile.education ?? [])],
        languages: [...(profile.languages?.items ?? [])],
        englishLevel: profile.languages?.english_level ?? '',
        certifications: [...(profile.certifications ?? [])],
    };
    rawMarkdown.value = profile.raw_md;
}

function addItem(list: string[]) {
    list.push('');
}

function removeItem(list: string[], index: number) {
    list.splice(index, 1);
}

async function uploadCv() {
    const file = fileInput.value?.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;
    uploadError.value = null;

    const formData = new FormData();
    formData.append('cv', file);

    try {
        const response = await fetch(importCv.url(), {
            method: 'post',
            headers: { Accept: 'application/json' },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            uploadError.value =
                data.message ?? 'No se pudo procesar la hoja de vida.';

            return;
        }

        await loadProfiles('default');
    } catch {
        uploadError.value = 'No se pudo conectar con el servidor.';
    } finally {
        uploading.value = false;

        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
}

async function activateSelected() {
    if (!selected.value) {
        return;
    }

    const { slug, label } = selected.value;

    try {
        await fetch(activate.url(slug), {
            method: 'post',
            headers: { Accept: 'application/json' },
        });

        await loadProfiles(slug);
        toast.success(`Variante "${label}" activada.`);
    } catch {
        toast.error('No se pudo activar la variante.');
    }
}

async function saveForm() {
    if (!selected.value) {
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const response = await fetch(updateVariant.url(selected.value.slug), {
            method: 'put',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                label: form.value.label,
                headline: form.value.headline || null,
                summary: form.value.summary || null,
                experience: form.value.experience.filter(
                    (item) => item.trim() !== '',
                ),
                skills: form.value.skills.filter((item) => item.trim() !== ''),
                education: form.value.education.filter(
                    (item) => item.trim() !== '',
                ),
                languages: {
                    items: form.value.languages.filter(
                        (item) => item.trim() !== '',
                    ),
                    english_level: form.value.englishLevel || null,
                },
                certifications: form.value.certifications.filter(
                    (item) => item.trim() !== '',
                ),
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            error.value = data.message ?? 'No se pudo guardar el perfil.';

            return;
        }

        await loadProfiles(selected.value.slug);
    } catch {
        error.value = 'No se pudo conectar con el servidor.';
    } finally {
        saving.value = false;
    }
}

async function syncFromMarkdown() {
    if (!selected.value) {
        return;
    }

    syncing.value = true;
    error.value = null;

    try {
        const response = await fetch(syncVariant.url(selected.value.slug), {
            method: 'post',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ content: rawMarkdown.value }),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = data.message ?? 'No se pudo sincronizar el perfil.';

            return;
        }

        await loadProfiles(selected.value.slug);
    } catch {
        error.value = 'No se pudo conectar con el servidor.';
    } finally {
        syncing.value = false;
    }
}

function actionLabel(action: ProfileSuggestionAction): string {
    return ACTION_LABELS[action];
}

async function requestReview() {
    if (!selected.value) {
        return;
    }

    reviewing.value = true;
    reviewError.value = null;
    suggestions.value = [];
    approved.value = {};
    reviewUsage.value = null;

    try {
        const response = await fetch(reviewWithAi.url(selected.value.slug), {
            method: 'post',
            headers: { Accept: 'application/json' },
        });

        const data = await response.json();

        if (!response.ok) {
            reviewError.value =
                data.message ?? 'No se pudo revisar el perfil con IA.';

            return;
        }

        suggestions.value = data.suggestions;
        reviewUsage.value = data.usage;
    } catch {
        reviewError.value = 'No se pudo conectar con el servidor.';
    } finally {
        reviewing.value = false;
    }
}

async function applyApproved() {
    if (!selected.value || approvedSuggestions.value.length === 0) {
        return;
    }

    const slug = selected.value.slug;
    applying.value = true;
    applyError.value = null;

    try {
        const response = await fetch(applySuggestions.url(slug), {
            method: 'post',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                suggestions: approvedSuggestions.value.map((suggestion) => ({
                    field: suggestion.field,
                    action: suggestion.action,
                    index: suggestion.index,
                    suggested: suggestion.suggested,
                })),
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            applyError.value =
                data.message ?? 'No se pudieron aplicar los cambios.';

            return;
        }

        suggestions.value = [];
        approved.value = {};
        reviewUsage.value = null;

        await loadProfiles(slug);
    } catch {
        applyError.value = 'No se pudo conectar con el servidor.';
    } finally {
        applying.value = false;
    }
}

async function createVariant() {
    if (!newVariantSlug.value || !newVariantLabel.value) {
        return;
    }

    creatingVariant.value = true;
    newVariantError.value = null;

    try {
        const response = await fetch(storeVariant.url(), {
            method: 'post',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                slug: newVariantSlug.value,
                label: newVariantLabel.value,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            newVariantError.value =
                data.message ?? 'No se pudo crear la variante.';

            return;
        }

        newVariantOpen.value = false;
        newVariantSlug.value = '';
        newVariantLabel.value = '';

        await loadProfiles(data.slug);
    } catch {
        newVariantError.value = 'No se pudo conectar con el servidor.';
    } finally {
        creatingVariant.value = false;
    }
}

const atsAnalyzing = ref(false);
const atsError = ref<string | null>(null);
const atsResult = ref<AtsAnalysis | null>(null);
const atsSaving = ref(false);
const atsVariantSaved = ref<string | null>(null);

async function analyzeAts() {
    atsAnalyzing.value = true;
    atsError.value = null;
    atsResult.value = null;
    atsVariantSaved.value = null;

    try {
        const response = await fetch('/api/profile/ats', {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();

        if (!response.ok) {
            atsError.value = data.message ?? 'No se pudo analizar el CV.';

            return;
        }

        atsResult.value = data;
    } catch {
        atsError.value = 'No se pudo conectar con el servidor.';
    } finally {
        atsAnalyzing.value = false;
    }
}

async function saveAtsVariant() {
    if (!atsResult.value) {
        return;
    }

    atsSaving.value = true;

    try {
        const response = await fetch('/api/profile/ats/confirm', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                version_optimizada_md: atsResult.value.after_markdown,
            }),
        });
        const data = await response.json();

        if (response.ok) {
            atsVariantSaved.value = data.slug;
            await loadProfiles(selectedSlug.value ?? undefined);
        } else {
            atsError.value = data.message ?? 'No se pudo guardar la variante.';
        }
    } catch {
        atsError.value = 'No se pudo conectar con el servidor.';
    } finally {
        atsSaving.value = false;
    }
}

const currentPassword = ref('');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const passwordSaving = ref(false);
const passwordError = ref<string | null>(null);
const passwordSuccess = ref<string | null>(null);

async function updatePassword() {
    passwordSaving.value = true;
    passwordError.value = null;
    passwordSuccess.value = null;

    try {
        const response = await fetch('/api/profile/password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirmation.value,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            passwordError.value = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message ?? 'No se pudo actualizar la contraseña.');

            return;
        }

        passwordSuccess.value = data.message;
        currentPassword.value = '';
        newPassword.value = '';
        newPasswordConfirmation.value = '';
    } catch {
        passwordError.value = 'No se pudo conectar con el servidor.';
    } finally {
        passwordSaving.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <header class="mb-7 border-b border-line pb-5">
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-card border border-line bg-surface text-primary shadow-card"
                    aria-hidden="true"
                >
                    <AppIcon name="profile" class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h1
                        class="text-3xl font-semibold tracking-[-0.04em] text-ink"
                    >
                        Perfil
                    </h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-ink-muted">
                        Mantén tus datos, variantes de CV y seguridad en un solo
                        lugar.
                    </p>
                </div>
            </div>
        </header>

        <BaseTabs
            v-model="activeTab"
            :items="TABS"
            label="Secciones del perfil"
        >
            <section
                v-if="activeTab === 'security'"
                class="pt-6"
                aria-labelledby="security-heading"
            >
                <BaseCard class="max-w-xl">
                    <div class="mb-5 border-b border-line pb-4">
                        <h2
                            id="security-heading"
                            class="text-lg font-semibold text-ink"
                        >
                            Cambiar contraseña
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-ink-muted">
                            Actualiza la contraseña de acceso a tu cuenta.
                        </p>
                    </div>

                    <form
                        class="flex flex-col gap-4"
                        @submit.prevent="updatePassword"
                    >
                        <BaseInput
                            v-model="currentPassword"
                            label="Contraseña actual"
                            type="password"
                            autocomplete="current-password"
                        />
                        <BaseInput
                            v-model="newPassword"
                            label="Nueva contraseña"
                            type="password"
                            autocomplete="new-password"
                        />
                        <BaseInput
                            v-model="newPasswordConfirmation"
                            label="Confirmar nueva contraseña"
                            type="password"
                            autocomplete="new-password"
                        />

                        <p
                            v-if="passwordError"
                            class="rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                            role="alert"
                        >
                            {{ passwordError }}
                        </p>
                        <p
                            v-if="passwordSuccess"
                            class="rounded-control bg-success-surface px-3 py-2 text-sm font-medium text-success"
                            role="status"
                            aria-live="polite"
                        >
                            {{ passwordSuccess }}
                        </p>

                        <div class="pt-1">
                            <BaseButton
                                type="submit"
                                :loading="passwordSaving"
                                loading-label="Actualizando contraseña"
                            >
                                Actualizar contraseña
                            </BaseButton>
                        </div>
                    </form>
                </BaseCard>
            </section>

            <template v-else>
                <div class="pt-6">
                    <BaseCard
                        v-if="activeTab === 'cv'"
                        variant="subtle"
                        class="mb-5"
                    >
                        <div
                            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <div class="max-w-2xl">
                                <h2 class="text-sm font-semibold text-ink">
                                    Subir hoja de vida
                                </h2>
                                <p
                                    class="mt-1 text-xs leading-5 text-ink-muted"
                                >
                                    Se parsea de forma determinista (sin IA) y
                                    reemplaza el perfil "default".
                                </p>
                            </div>
                            <div
                                class="flex flex-col items-stretch gap-2 sm:items-end"
                            >
                                <label for="profile-cv-upload" class="sr-only">
                                    Seleccionar hoja de vida
                                </label>
                                <input
                                    id="profile-cv-upload"
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf,.txt,.md"
                                    class="max-w-full rounded-control border border-line-strong bg-surface px-3 py-2 text-xs text-ink file:mr-3 file:rounded-control file:border-0 file:bg-primary-subtle file:px-3 file:py-1.5 file:font-semibold file:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus sm:max-w-80"
                                />
                                <BaseButton
                                    size="sm"
                                    :loading="uploading"
                                    loading-label="Importando hoja de vida"
                                    @click="uploadCv"
                                >
                                    Subir CV
                                </BaseButton>
                            </div>
                        </div>
                        <p
                            v-if="uploadError"
                            class="mt-3 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                            role="alert"
                        >
                            {{ uploadError }}
                        </p>
                    </BaseCard>

                    <BaseCard
                        variant="subtle"
                        class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                        aria-label="Contexto de variante de perfil"
                    >
                        <div
                            class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(0,22rem)_auto] sm:items-end"
                        >
                            <BaseSelect
                                v-model="selectedProfileSlug"
                                label="Variante"
                                :options="profileOptions"
                                :disabled="profiles.length === 0"
                            />
                            <BaseTag
                                v-if="selected?.is_active"
                                tone="success"
                                class="mb-2.5 w-fit"
                            >
                                Variante activa
                            </BaseTag>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <BaseButton
                                v-if="selected && !selected.is_active"
                                size="sm"
                                variant="secondary"
                                @click="activateSelected"
                            >
                                Activar
                            </BaseButton>
                            <BaseButton
                                size="sm"
                                variant="quiet"
                                :aria-expanded="newVariantOpen"
                                aria-controls="new-profile-variant"
                                @click="newVariantOpen = !newVariantOpen"
                            >
                                Nueva variante
                            </BaseButton>
                        </div>
                    </BaseCard>

                    <BaseCard
                        v-if="newVariantOpen"
                        id="new-profile-variant"
                        class="mb-5"
                    >
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold text-ink">
                                Crear variante
                            </h2>
                            <p class="mt-1 text-xs leading-5 text-ink-muted">
                                La nueva variante clona el perfil "default".
                            </p>
                        </div>
                        <div
                            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end"
                        >
                            <BaseInput
                                v-model="newVariantSlug"
                                label="Slug"
                                placeholder="backend"
                            />
                            <BaseInput
                                v-model="newVariantLabel"
                                label="Nombre"
                                placeholder="Enfoque backend"
                            />
                            <BaseButton
                                :loading="creatingVariant"
                                loading-label="Creando variante"
                                @click="createVariant"
                            >
                                Crear (clona "default")
                            </BaseButton>
                        </div>
                        <p
                            v-if="newVariantError"
                            class="mt-3 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                            role="alert"
                        >
                            {{ newVariantError }}
                        </p>
                    </BaseCard>

                    <div
                        v-if="loading"
                        class="flex flex-col gap-3"
                        role="status"
                        aria-label="Cargando perfil"
                    >
                        <BaseSkeleton shape="text" class="w-1/3" />
                        <BaseSkeleton class="h-28 w-full" />
                        <BaseSkeleton class="h-40 w-full" />
                    </div>

                    <template v-else-if="selected">
                        <BaseCard
                            v-if="activeTab === 'personal'"
                            aria-labelledby="personal-profile-heading"
                        >
                            <div class="mb-6 border-b border-line pb-4">
                                <div
                                    class="flex flex-wrap items-center justify-between gap-3"
                                >
                                    <div>
                                        <h2
                                            id="personal-profile-heading"
                                            class="text-lg font-semibold text-ink"
                                        >
                                            Editar "{{ selected.label }}"
                                        </h2>
                                        <p
                                            class="mt-1 text-sm leading-6 text-ink-muted"
                                        >
                                            Estos datos alimentan el análisis de
                                            vacantes y tus variantes de CV.
                                        </p>
                                    </div>
                                    <BaseTag
                                        :tone="
                                            selected.is_active
                                                ? 'success'
                                                : 'neutral'
                                        "
                                    >
                                        {{
                                            selected.is_active
                                                ? 'Variante activa'
                                                : 'Variante inactiva'
                                        }}
                                    </BaseTag>
                                </div>
                            </div>

                            <div class="grid gap-5">
                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <BaseInput
                                        v-model="form.label"
                                        label="Nombre del perfil"
                                    />
                                    <BaseInput
                                        v-model="form.headline"
                                        label="Titular"
                                    />
                                </div>

                                <BaseTextarea
                                    v-model="form.summary"
                                    label="Resumen"
                                    :rows="4"
                                />

                                <section
                                    v-for="listConfig in [
                                        {
                                            key: 'experience' as const,
                                            label: 'Experiencia',
                                        },
                                        {
                                            key: 'skills' as const,
                                            label: 'Skills',
                                        },
                                        {
                                            key: 'education' as const,
                                            label: 'Educación',
                                        },
                                        {
                                            key: 'certifications' as const,
                                            label: 'Certificaciones',
                                        },
                                    ]"
                                    :key="listConfig.key"
                                    class="border-t border-line pt-5"
                                >
                                    <h3
                                        class="mb-3 text-sm font-semibold text-ink"
                                    >
                                        {{ listConfig.label }}
                                    </h3>
                                    <div class="grid gap-3">
                                        <div
                                            v-for="(_, index) in form[
                                                listConfig.key
                                            ]"
                                            :key="index"
                                            class="flex items-end gap-2"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <BaseInput
                                                    v-model="
                                                        form[listConfig.key][
                                                            index
                                                        ]
                                                    "
                                                    :label="`${listConfig.label} ${index + 1}`"
                                                />
                                            </div>
                                            <BaseButton
                                                variant="quiet"
                                                size="icon"
                                                :aria-label="`Eliminar ${listConfig.label.toLowerCase()} ${index + 1}`"
                                                @click="
                                                    removeItem(
                                                        form[listConfig.key],
                                                        index,
                                                    )
                                                "
                                            >
                                                <AppIcon
                                                    name="close"
                                                    class="h-4 w-4"
                                                />
                                            </BaseButton>
                                        </div>
                                    </div>
                                    <BaseButton
                                        size="sm"
                                        variant="quiet"
                                        class="mt-2"
                                        @click="addItem(form[listConfig.key])"
                                    >
                                        Agregar
                                        {{ listConfig.label.toLowerCase() }}
                                    </BaseButton>
                                </section>

                                <section class="border-t border-line pt-5">
                                    <h3
                                        class="mb-3 text-sm font-semibold text-ink"
                                    >
                                        Idiomas
                                    </h3>
                                    <div class="grid gap-3">
                                        <div
                                            v-for="(_, index) in form.languages"
                                            :key="index"
                                            class="flex items-end gap-2"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <BaseInput
                                                    v-model="
                                                        form.languages[index]
                                                    "
                                                    :label="`Idioma ${index + 1}`"
                                                />
                                            </div>
                                            <BaseButton
                                                variant="quiet"
                                                size="icon"
                                                :aria-label="`Eliminar idioma ${index + 1}`"
                                                @click="
                                                    removeItem(
                                                        form.languages,
                                                        index,
                                                    )
                                                "
                                            >
                                                <AppIcon
                                                    name="close"
                                                    class="h-4 w-4"
                                                />
                                            </BaseButton>
                                        </div>
                                    </div>
                                    <BaseButton
                                        size="sm"
                                        variant="quiet"
                                        class="mt-2"
                                        @click="addItem(form.languages)"
                                    >
                                        Agregar idioma
                                    </BaseButton>

                                    <div class="mt-4 max-w-xs">
                                        <BaseSelect
                                            v-model="form.englishLevel"
                                            label="Nivel de inglés declarado"
                                            :options="englishLevelOptions"
                                        />
                                    </div>
                                </section>
                            </div>

                            <p
                                v-if="error"
                                class="mt-5 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                                role="alert"
                            >
                                {{ error }}
                            </p>

                            <div
                                class="mt-6 flex justify-end border-t border-line pt-5"
                            >
                                <BaseButton
                                    :loading="saving"
                                    loading-label="Guardando perfil"
                                    @click="saveForm"
                                >
                                    Guardar
                                </BaseButton>
                            </div>
                        </BaseCard>

                        <BaseCard
                            v-if="activeTab === 'cv'"
                            class="mb-5"
                            aria-labelledby="ai-review-heading"
                        >
                            <div
                                class="mb-4 flex flex-col justify-between gap-4 border-b border-line pb-4 sm:flex-row sm:items-start"
                            >
                                <div class="max-w-2xl">
                                    <h2
                                        id="ai-review-heading"
                                        class="text-lg font-semibold text-ink"
                                    >
                                        Revisión con IA
                                    </h2>
                                    <p
                                        class="mt-1 text-sm leading-6 text-ink-muted"
                                    >
                                        Compara el CV original con el perfil
                                        parseado y sugiere correcciones y
                                        mejoras. Nada se aplica automáticamente:
                                        aprueba cada sugerencia antes de
                                        aplicarla.
                                    </p>
                                </div>
                                <BaseButton
                                    size="sm"
                                    variant="secondary"
                                    :disabled="!selected.source_text"
                                    :loading="reviewing"
                                    loading-label="Revisando el CV con IA"
                                    @click="requestReview"
                                >
                                    Revisar con IA
                                </BaseButton>
                            </div>

                            <p
                                v-if="!selected.source_text"
                                class="rounded-control bg-surface-subtle px-3 py-2 text-sm leading-6 text-ink-muted"
                                role="status"
                            >
                                Este perfil no tiene el CV original guardado.
                                Reimporta la hoja de vida para habilitar la
                                revisión con IA.
                            </p>

                            <p
                                v-if="reviewError"
                                class="mb-3 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                                role="alert"
                            >
                                {{ reviewError }}
                            </p>

                            <p
                                v-if="reviewUsage"
                                class="mb-4 font-data text-xs text-ink-subtle tabular-nums"
                                role="status"
                                aria-live="polite"
                            >
                                Duración:
                                {{ formatDuration(reviewUsage.durationMs) }} ·
                                Coste: {{ formatCost(reviewUsage.costUsd) }}
                            </p>

                            <div
                                v-if="suggestions.length > 0"
                                class="grid gap-6"
                            >
                                <section
                                    v-for="group in [
                                        {
                                            key: 'correction' as const,
                                            label: 'Correcciones',
                                            items: corrections,
                                        },
                                        {
                                            key: 'improvement' as const,
                                            label: 'Mejoras',
                                            items: improvements,
                                        },
                                    ]"
                                    v-show="group.items.length > 0"
                                    :key="group.key"
                                >
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <h3
                                            class="text-sm font-semibold text-ink"
                                        >
                                            {{ group.label }}
                                        </h3>
                                        <BaseTag
                                            :tone="
                                                group.key === 'correction'
                                                    ? 'warning'
                                                    : 'info'
                                            "
                                        >
                                            {{ group.items.length }}
                                        </BaseTag>
                                    </div>

                                    <div class="grid gap-3">
                                        <article
                                            v-for="suggestion in group.items"
                                            :key="suggestion.id"
                                            class="rounded-card border border-line bg-surface-subtle p-4"
                                        >
                                            <div class="flex items-start gap-3">
                                                <input
                                                    :id="suggestion.id"
                                                    type="checkbox"
                                                    class="mt-1 h-4 w-4 shrink-0 accent-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                                                    :checked="
                                                        !!approved[
                                                            suggestion.id
                                                        ]
                                                    "
                                                    @change="
                                                        approved[
                                                            suggestion.id
                                                        ] = (
                                                            $event.target as HTMLInputElement
                                                        ).checked
                                                    "
                                                />
                                                <div class="min-w-0 flex-1">
                                                    <label
                                                        :for="suggestion.id"
                                                        class="flex cursor-pointer flex-wrap items-center gap-2"
                                                    >
                                                        <span
                                                            class="font-semibold text-ink"
                                                        >
                                                            {{
                                                                FIELD_LABELS[
                                                                    suggestion
                                                                        .field
                                                                ]
                                                            }}
                                                            <template
                                                                v-if="
                                                                    suggestion.index !==
                                                                    null
                                                                "
                                                            >
                                                                #{{
                                                                    suggestion.index +
                                                                    1
                                                                }}
                                                            </template>
                                                        </span>
                                                        <BaseTag>
                                                            {{
                                                                actionLabel(
                                                                    suggestion.action,
                                                                )
                                                            }}
                                                        </BaseTag>
                                                    </label>

                                                    <div
                                                        class="mt-3 grid gap-3 sm:grid-cols-2"
                                                    >
                                                        <div
                                                            class="rounded-control border border-line bg-surface p-3"
                                                        >
                                                            <p
                                                                class="mb-1 text-xs font-semibold text-ink-subtle"
                                                            >
                                                                Actual
                                                            </p>
                                                            <p
                                                                class="text-sm leading-6 text-ink-muted"
                                                            >
                                                                {{
                                                                    suggestion.current ||
                                                                    'Sin contenido actual'
                                                                }}
                                                            </p>
                                                        </div>
                                                        <div
                                                            class="rounded-control border border-primary/20 bg-primary-subtle p-3"
                                                        >
                                                            <p
                                                                class="mb-1 text-xs font-semibold text-primary"
                                                            >
                                                                Sugerencia
                                                            </p>
                                                            <p
                                                                class="text-sm leading-6 text-ink"
                                                            >
                                                                {{
                                                                    suggestion.suggested ||
                                                                    'Sin contenido sugerido'
                                                                }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <p
                                                        class="mt-3 text-xs leading-5 text-ink-subtle italic"
                                                    >
                                                        {{
                                                            suggestion.rationale
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                </section>

                                <p
                                    v-if="applyError"
                                    class="rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                                    role="alert"
                                >
                                    {{ applyError }}
                                </p>

                                <div
                                    class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5"
                                >
                                    <p class="text-sm text-ink-muted">
                                        {{ approvedSuggestions.length }} de
                                        {{ suggestions.length }} seleccionadas
                                    </p>
                                    <BaseButton
                                        :disabled="
                                            approvedSuggestions.length === 0
                                        "
                                        :loading="applying"
                                        loading-label="Aplicando sugerencias seleccionadas"
                                        @click="applyApproved"
                                    >
                                        Aplicar seleccionadas ({{
                                            approvedSuggestions.length
                                        }})
                                    </BaseButton>
                                </div>
                            </div>

                            <p
                                v-else-if="!reviewing && !reviewError"
                                class="text-sm text-ink-muted"
                                role="status"
                            >
                                Sin sugerencias todavía.
                            </p>
                        </BaseCard>

                        <BaseCard
                            v-if="activeTab === 'cv'"
                            aria-labelledby="markdown-editor-heading"
                        >
                            <div class="mb-4 border-b border-line pb-4">
                                <h2
                                    id="markdown-editor-heading"
                                    class="text-lg font-semibold text-ink"
                                >
                                    Editor Markdown crudo
                                </h2>
                                <p
                                    class="mt-1 max-w-3xl text-sm leading-6 text-ink-muted"
                                >
                                    Edita el `perfil.md` directamente y
                                    sincroniza para volver a estructurarlo
                                    (determinista, sin IA). Solo el perfil
                                    activo puede sincronizarse.
                                </p>
                            </div>
                            <BaseTextarea
                                v-model="rawMarkdown"
                                label="Contenido de perfil.md"
                                :rows="14"
                                class="font-data"
                            />
                            <p
                                v-if="error"
                                class="mt-4 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                                role="alert"
                            >
                                {{ error }}
                            </p>
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <BaseButton
                                    variant="secondary"
                                    :disabled="!selected.is_active"
                                    :loading="syncing"
                                    loading-label="Sincronizando perfil"
                                    @click="syncFromMarkdown"
                                >
                                    Sincronizar
                                </BaseButton>
                                <span
                                    v-if="!selected.is_active"
                                    class="text-xs text-ink-muted"
                                    role="status"
                                >
                                    Activa este perfil para poder sincronizarlo.
                                </span>
                            </div>
                        </BaseCard>

                        <BaseCard
                            v-if="activeTab === 'ats'"
                            aria-labelledby="ats-heading"
                        >
                            <div
                                class="mb-5 flex flex-col justify-between gap-4 border-b border-line pb-4 sm:flex-row sm:items-start"
                            >
                                <div class="max-w-2xl">
                                    <h2
                                        id="ats-heading"
                                        class="text-lg font-semibold text-ink"
                                    >
                                        Optimización ATS
                                    </h2>
                                    <p
                                        class="mt-1 text-sm leading-6 text-ink-muted"
                                    >
                                        Evalúa el perfil activo contra criterios
                                        estándar de compatibilidad ATS. Ningún
                                        CV es infalible ante un sistema ATS: usa
                                        esto como guía, no como garantía de
                                        resultados.
                                    </p>
                                </div>
                                <BaseButton
                                    size="sm"
                                    variant="secondary"
                                    :loading="atsAnalyzing"
                                    loading-label="Analizando compatibilidad ATS"
                                    @click="analyzeAts"
                                >
                                    Analizar con IA
                                </BaseButton>
                            </div>

                            <p
                                v-if="atsError"
                                class="mb-4 rounded-control bg-error-surface px-3 py-2 text-sm font-medium text-error"
                                role="alert"
                            >
                                {{ atsError }}
                            </p>

                            <div v-if="atsResult" class="grid gap-6">
                                <div
                                    class="grid gap-5 rounded-card border border-line bg-surface-subtle p-4 sm:grid-cols-[auto_1fr] sm:items-center sm:p-5"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <div class="text-center">
                                        <MatchScore
                                            :score="atsResult.ats_score"
                                            size="hero"
                                        />
                                        <p
                                            class="mt-2 text-xs font-semibold text-ink"
                                        >
                                            Score ATS
                                        </p>
                                    </div>
                                    <div
                                        class="grid gap-3 border-t border-line pt-4 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-5"
                                    >
                                        <p
                                            class="text-sm leading-6 text-ink-muted"
                                        >
                                            El score resume la compatibilidad
                                            técnica estimada del documento.
                                            Revisa los problemas y la
                                            comparación antes de guardar una
                                            variante.
                                        </p>
                                        <dl
                                            class="grid grid-cols-2 gap-3 font-data text-xs text-ink-subtle tabular-nums"
                                        >
                                            <div>
                                                <dt>Duración</dt>
                                                <dd
                                                    class="mt-1 font-semibold text-ink"
                                                >
                                                    {{
                                                        formatDuration(
                                                            atsResult.usage
                                                                .durationMs,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Coste</dt>
                                                <dd
                                                    class="mt-1 font-semibold text-ink"
                                                >
                                                    {{
                                                        formatCost(
                                                            atsResult.usage
                                                                .costUsd,
                                                        )
                                                    }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <div class="grid gap-5 lg:grid-cols-2">
                                    <section
                                        v-if="atsResult.problemas.length"
                                        class="rounded-card border border-line bg-surface p-4"
                                    >
                                        <h3
                                            class="mb-3 text-sm font-semibold text-ink"
                                        >
                                            Problemas priorizados
                                        </h3>
                                        <ul
                                            class="grid gap-2 text-sm leading-6 text-ink-muted"
                                        >
                                            <li
                                                v-for="(
                                                    problema, index
                                                ) in atsResult.problemas"
                                                :key="index"
                                                class="flex gap-2 before:mt-2.5 before:h-1 before:w-1 before:shrink-0 before:rounded-full before:bg-warning"
                                            >
                                                {{ problema }}
                                            </li>
                                        </ul>
                                    </section>

                                    <section
                                        v-if="
                                            atsResult.recomendaciones_formato
                                                .length
                                        "
                                        class="rounded-card border border-line bg-surface p-4"
                                    >
                                        <h3
                                            class="mb-3 text-sm font-semibold text-ink"
                                        >
                                            Recomendaciones de formato
                                        </h3>
                                        <ul
                                            class="grid gap-2 text-sm leading-6 text-ink-muted"
                                        >
                                            <li
                                                v-for="(
                                                    recomendacion, index
                                                ) in atsResult.recomendaciones_formato"
                                                :key="index"
                                                class="flex gap-2 before:mt-2.5 before:h-1 before:w-1 before:shrink-0 before:rounded-full before:bg-info"
                                            >
                                                {{ recomendacion }}
                                            </li>
                                        </ul>
                                    </section>
                                </div>

                                <section
                                    v-if="atsResult.keywords_faltantes.length"
                                >
                                    <h3
                                        class="mb-3 text-sm font-semibold text-ink"
                                    >
                                        Keywords faltantes
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <BaseTag
                                            v-for="(
                                                keyword, index
                                            ) in atsResult.keywords_faltantes"
                                            :key="index"
                                            tone="primary"
                                        >
                                            {{ keyword }}
                                        </BaseTag>
                                    </div>
                                </section>

                                <section class="border-t border-line pt-5">
                                    <h3
                                        class="mb-1 text-sm font-semibold text-ink"
                                    >
                                        Versión optimizada (previsualización)
                                    </h3>
                                    <p
                                        class="mb-3 text-xs leading-5 text-ink-muted"
                                    >
                                        Revisa el contenido actual y el
                                        propuesto antes de guardar una nueva
                                        variante.
                                    </p>
                                    <DiffViewer
                                        :before="atsResult.before_markdown"
                                        :after="atsResult.after_markdown"
                                    />

                                    <div
                                        class="mt-4 flex flex-wrap items-center gap-3"
                                    >
                                        <BaseButton
                                            size="sm"
                                            :loading="atsSaving"
                                            loading-label="Guardando variante ATS"
                                            @click="saveAtsVariant"
                                        >
                                            Guardar como variante
                                        </BaseButton>
                                        <span
                                            v-if="atsVariantSaved"
                                            class="text-xs font-medium text-success"
                                            role="status"
                                            aria-live="polite"
                                        >
                                            Guardado como variante "{{
                                                atsVariantSaved
                                            }}"
                                        </span>
                                    </div>
                                </section>
                            </div>
                        </BaseCard>
                    </template>

                    <EmptyState
                        v-else
                        title="Tu perfil todavía está vacío"
                        description='Sube tu hoja de vida para crear el perfil "default".'
                    >
                        <template #icon>
                            <AppIcon name="profile" class="h-6 w-6" />
                        </template>
                        <template #action>
                            <BaseButton @click="activeTab = 'cv'">
                                Subir CV
                            </BaseButton>
                        </template>
                    </EmptyState>
                </div>
            </template>
        </BaseTabs>
    </div>
</template>
