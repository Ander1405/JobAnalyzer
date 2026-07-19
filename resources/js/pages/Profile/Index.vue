<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { importMethod as importCv } from '@/actions/App/Http/Controllers/Api/ProfileController';
import {
    activate,
    index as profilesIndex,
    store as storeVariant,
    sync as syncVariant,
    update as updateVariant,
} from '@/actions/App/Http/Controllers/Api/ProfileVariantController';
import type { Profile } from '@/types/profile';

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
    } finally {
        loading.value = false;
    }
}

function selectProfile(slug: string | null) {
    selectedSlug.value = slug;
    const profile = profiles.value.find((item) => item.slug === slug) ?? null;

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

    await fetch(activate.url(selected.value.slug), {
        method: 'post',
        headers: { Accept: 'application/json' },
    });

    await loadProfiles(selected.value.slug);
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
    } finally {
        syncing.value = false;
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
    } finally {
        creatingVariant.value = false;
    }
}
</script>

<template>
    <Head title="Perfil — JobHunter" />

    <div
        class="min-h-screen bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <div class="mx-auto max-w-4xl">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Perfil</h1>
                <Link
                    href="/"
                    class="text-sm text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ← Volver a vacantes
                </Link>
            </div>

            <section
                class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="mb-2 text-sm font-semibold">Subir hoja de vida</h2>
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    Se parsea de forma determinista (sin IA) y reemplaza el
                    perfil "default".
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.txt,.md"
                        class="max-w-56 text-xs"
                    />
                    <button
                        type="button"
                        :disabled="uploading"
                        class="rounded-md bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                        @click="uploadCv"
                    >
                        {{ uploading ? 'Importando…' : 'Subir CV' }}
                    </button>
                </div>
                <p
                    v-if="uploadError"
                    class="mt-2 text-xs text-red-600 dark:text-red-400"
                >
                    {{ uploadError }}
                </p>
            </section>

            <section class="mb-6 flex flex-wrap items-center gap-3">
                <label class="text-sm text-gray-600 dark:text-gray-400"
                    >Variante:</label
                >
                <select
                    :value="selectedSlug"
                    class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                    @change="
                        selectProfile(
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="profile in profiles"
                        :key="profile.slug"
                        :value="profile.slug"
                    >
                        {{ profile.label }} ({{ profile.slug }}){{
                            profile.is_active ? ' · activa' : ''
                        }}
                    </option>
                </select>

                <button
                    v-if="selected && !selected.is_active"
                    type="button"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="activateSelected"
                >
                    Activar
                </button>

                <button
                    type="button"
                    class="text-xs text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
                    @click="newVariantOpen = !newVariantOpen"
                >
                    Nueva variante
                </button>
            </section>

            <section
                v-if="newVariantOpen"
                class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-800"
            >
                <div>
                    <label class="block text-xs text-gray-500">Slug</label>
                    <input
                        v-model="newVariantSlug"
                        type="text"
                        placeholder="backend"
                        class="rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Nombre</label>
                    <input
                        v-model="newVariantLabel"
                        type="text"
                        placeholder="Enfoque backend"
                        class="rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </div>
                <button
                    type="button"
                    :disabled="creatingVariant"
                    class="rounded-md bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                    @click="createVariant"
                >
                    Crear (clona "default")
                </button>
                <p
                    v-if="newVariantError"
                    class="w-full text-xs text-red-600 dark:text-red-400"
                >
                    {{ newVariantError }}
                </p>
            </section>

            <p v-if="loading" class="text-sm text-gray-500">Cargando…</p>

            <template v-else-if="selected">
                <section
                    class="mb-6 rounded-lg border border-gray-200 p-4 dark:border-gray-800"
                >
                    <h2 class="mb-3 text-sm font-semibold">
                        Editar "{{ selected.label }}"
                    </h2>

                    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs text-gray-500"
                                >Nombre del perfil</label
                            >
                            <input
                                v-model="form.label"
                                type="text"
                                class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500"
                                >Titular</label
                            >
                            <input
                                v-model="form.headline"
                                type="text"
                                class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs text-gray-500"
                            >Resumen</label
                        >
                        <textarea
                            v-model="form.summary"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                        />
                    </div>

                    <div
                        v-for="listConfig in [
                            {
                                key: 'experience' as const,
                                label: 'Experiencia',
                            },
                            { key: 'skills' as const, label: 'Skills' },
                            { key: 'education' as const, label: 'Educación' },
                            {
                                key: 'certifications' as const,
                                label: 'Certificaciones',
                            },
                        ]"
                        :key="listConfig.key"
                        class="mb-3"
                    >
                        <label class="block text-xs text-gray-500">{{
                            listConfig.label
                        }}</label>
                        <div
                            v-for="(_, index) in form[listConfig.key]"
                            :key="index"
                            class="mb-1 flex items-center gap-2"
                        >
                            <input
                                v-model="form[listConfig.key][index]"
                                type="text"
                                class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                            />
                            <button
                                type="button"
                                class="text-gray-400 hover:text-red-600"
                                @click="removeItem(form[listConfig.key], index)"
                            >
                                ✕
                            </button>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
                            @click="addItem(form[listConfig.key])"
                        >
                            + Agregar
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs text-gray-500"
                            >Idiomas</label
                        >
                        <div
                            v-for="(_, index) in form.languages"
                            :key="index"
                            class="mb-1 flex items-center gap-2"
                        >
                            <input
                                v-model="form.languages[index]"
                                type="text"
                                class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                            />
                            <button
                                type="button"
                                class="text-gray-400 hover:text-red-600"
                                @click="removeItem(form.languages, index)"
                            >
                                ✕
                            </button>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-gray-500 underline hover:text-gray-700 dark:hover:text-gray-300"
                            @click="addItem(form.languages)"
                        >
                            + Agregar
                        </button>

                        <div class="mt-2">
                            <label class="block text-xs text-gray-500"
                                >Nivel de inglés declarado</label
                            >
                            <select
                                v-model="form.englishLevel"
                                class="rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                            >
                                <option value="">No especificado</option>
                                <option
                                    v-for="level in ENGLISH_LEVELS"
                                    :key="level"
                                    :value="level"
                                >
                                    {{ level }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <p
                        v-if="error"
                        class="mb-2 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ error }}
                    </p>

                    <button
                        type="button"
                        :disabled="saving"
                        class="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                        @click="saveForm"
                    >
                        {{ saving ? 'Guardando…' : 'Guardar' }}
                    </button>
                </section>

                <section
                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-800"
                >
                    <h2 class="mb-2 text-sm font-semibold">
                        Editor Markdown crudo
                    </h2>
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Edita el `perfil.md` directamente y sincroniza para
                        volver a estructurarlo (determinista, sin IA). Solo el
                        perfil activo puede sincronizarse.
                    </p>
                    <textarea
                        v-model="rawMarkdown"
                        rows="14"
                        class="w-full rounded-md border border-gray-300 bg-white p-3 font-mono text-xs dark:border-gray-700 dark:bg-gray-950"
                    />
                    <button
                        type="button"
                        :disabled="syncing || !selected.is_active"
                        class="mt-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-100 disabled:opacity-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        @click="syncFromMarkdown"
                    >
                        {{ syncing ? 'Sincronizando…' : 'Sincronizar' }}
                    </button>
                    <span
                        v-if="!selected.is_active"
                        class="ml-2 text-xs text-gray-500"
                    >
                        Activa este perfil para poder sincronizarlo.
                    </span>
                </section>
            </template>

            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                Sube tu hoja de vida para crear el perfil "default".
            </p>
        </div>
    </div>
</template>
