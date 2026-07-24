<script setup lang="ts">
import { router as inertiaRouter, usePage } from '@inertiajs/vue3';
import { AnimatePresence, motion } from 'motion-v';
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseButton,
    BaseCard,
    BaseInput,
    BaseModal,
    BaseSelect,
    BaseSkeleton,
    BaseTag,
    EmptyState,
} from '@/components/ui';
import { useToast } from '@/lib/toast';
import type {
    AdminRole,
    AdminUser,
    ApiValidationErrors,
    PaginatedAdminUsers,
    RoleIndexResponse,
} from '@/types/admin';

type ApiFailure = {
    message?: string;
    errors?: ApiValidationErrors;
};

const pageProps = usePage();
const toast = useToast();
const users = ref<PaginatedAdminUsers>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
});
const roles = ref<AdminRole[]>([]);
const search = ref('');
const roleFilter = ref<string | null>('');
const loading = ref(true);
const loadError = ref<string | null>(null);
const modalOpen = ref(false);
const editingUser = ref<AdminUser | null>(null);
const saving = ref(false);
const formErrors = ref<ApiValidationErrors>({});
const deleteTarget = ref<AdminUser | null>(null);
const deleting = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let latestRequest = 0;

const form = reactive({
    name: '',
    email: '',
    password: '',
    roles: [] as string[],
});

const roleOptions = ref([{ value: '', label: 'Todos los roles' }]);
const dateFormatter = new Intl.DateTimeFormat('es', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

onMounted(async () => {
    await Promise.all([loadRoles(), loadUsers()]);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

watch([search, roleFilter], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => void loadUsers(1), 300);
});

async function loadRoles(): Promise<void> {
    try {
        const response = await fetch('/api/admin/roles', {
            headers: { Accept: 'application/json' },
        });
        const data = await readResponse<RoleIndexResponse>(response);
        roles.value = data.data;
        roleOptions.value = [
            { value: '', label: 'Todos los roles' },
            ...data.data.map((role) => ({
                value: role.name,
                label: role.name,
            })),
        ];
    } catch (error) {
        toast.error(failureMessage(error, 'No se pudieron cargar los roles.'));
    }
}

async function loadUsers(targetPage = users.value.current_page): Promise<void> {
    const requestId = ++latestRequest;
    loading.value = true;
    loadError.value = null;

    const params = new URLSearchParams({
        page: String(targetPage),
        per_page: String(users.value.per_page),
    });

    if (search.value.trim()) {
        params.set('search', search.value.trim());
    }

    if (roleFilter.value) {
        params.set('role', roleFilter.value);
    }

    try {
        const response = await fetch(`/api/admin/users?${params}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await readResponse<PaginatedAdminUsers>(response);

        if (requestId === latestRequest) {
            users.value = data;
        }
    } catch (error) {
        if (requestId === latestRequest) {
            loadError.value = failureMessage(
                error,
                'No se pudieron cargar los usuarios.',
            );
            toast.error(loadError.value);
        }
    } finally {
        if (requestId === latestRequest) {
            loading.value = false;
        }
    }
}

function openCreate(): void {
    editingUser.value = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.roles = roles.value.some((role) => role.name === 'user')
        ? ['user']
        : [];
    formErrors.value = {};
    modalOpen.value = true;
}

function openEdit(user: AdminUser): void {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.roles = user.roles.map((role) => role.name);
    formErrors.value = {};
    modalOpen.value = true;
}

function toggleRole(roleName: string): void {
    const index = form.roles.indexOf(roleName);

    if (index === -1) {
        form.roles.push(roleName);
    } else {
        form.roles.splice(index, 1);
    }
}

async function saveUser(): Promise<void> {
    saving.value = true;
    formErrors.value = {};

    const endpoint = editingUser.value
        ? `/api/admin/users/${editingUser.value.id}`
        : '/api/admin/users';

    try {
        const updatesCurrentUser =
            editingUser.value?.id === pageProps.props.auth.user.id;
        const response = await fetch(endpoint, {
            method: editingUser.value ? 'PUT' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: form.name,
                email: form.email,
                password: form.password || null,
                roles: form.roles,
            }),
        });
        await readResponse<AdminUser>(response);

        const wasEditing = editingUser.value !== null;
        modalOpen.value = false;
        toast.success(wasEditing ? 'Usuario actualizado.' : 'Usuario creado.');
        await loadUsers(wasEditing ? users.value.current_page : 1);

        if (updatesCurrentUser) {
            inertiaRouter.reload({ only: ['auth'] });
        }
    } catch (error) {
        const failure = error as ApiFailure;
        formErrors.value = failure.errors ?? {};

        if (!failure.errors) {
            toast.error(
                failureMessage(error, 'No se pudo guardar el usuario.'),
            );
        }
    } finally {
        saving.value = false;
    }
}

async function deleteUser(): Promise<void> {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    try {
        const response = await fetch(
            `/api/admin/users/${deleteTarget.value.id}`,
            {
                method: 'DELETE',
                headers: { Accept: 'application/json' },
            },
        );
        await readResponse<null>(response);
        deleteTarget.value = null;
        toast.success('Usuario eliminado.');

        const targetPage =
            users.value.data.length === 1 && users.value.current_page > 1
                ? users.value.current_page - 1
                : users.value.current_page;
        await loadUsers(targetPage);
    } catch (error) {
        toast.error(failureMessage(error, 'No se pudo eliminar el usuario.'));
    } finally {
        deleting.value = false;
    }
}

function firstError(field: string): string | undefined {
    return formErrors.value[field]?.[0];
}

function rolesError(): string | undefined {
    const key = Object.keys(formErrors.value).find(
        (field) => field === 'roles' || field.startsWith('roles.'),
    );

    return key ? formErrors.value[key]?.[0] : undefined;
}

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

function isCurrentUser(user: AdminUser): boolean {
    return user.id === pageProps.props.auth.user.id;
}

async function readResponse<T>(response: Response): Promise<T> {
    const data = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        throw data as ApiFailure;
    }

    return data as T;
}

function failureMessage(error: unknown, fallback: string): string {
    return (error as ApiFailure)?.message ?? fallback;
}
</script>

<template>
    <div class="mx-auto max-w-7xl">
        <header class="mb-5 border-b border-line pb-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="mb-2 text-step-eyebrow font-semibold text-primary"
                    >
                        ADMINISTRACIÓN
                    </p>
                    <h1
                        class="text-step-h2 font-semibold tracking-[-0.04em] text-balance text-ink"
                    >
                        Usuarios
                    </h1>
                    <p
                        class="mt-1 max-w-2xl text-step-body leading-6 text-ink-muted"
                    >
                        Gestiona el acceso de tu equipo y mantén claros sus
                        roles dentro de JobHunter.
                    </p>
                </div>

                <BaseButton @click="openCreate">
                    <template #leading>
                        <AppIcon name="users" class="h-4 w-4" />
                    </template>
                    Crear usuario
                </BaseButton>
            </div>

            <nav
                class="mt-5 flex gap-1"
                aria-label="Secciones de administración"
            >
                <router-link
                    to="/admin/users"
                    class="rounded-control bg-primary-subtle px-3 py-2 text-sm font-semibold text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                    aria-current="page"
                >
                    Usuarios
                </router-link>
                <router-link
                    to="/admin/roles"
                    class="rounded-control px-3 py-2 text-sm font-semibold text-ink-muted hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                >
                    Roles y permisos
                </router-link>
            </nav>
        </header>

        <BaseCard class="mb-4" variant="subtle">
            <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_16rem]">
                <BaseInput
                    v-model="search"
                    label="Buscar usuarios"
                    type="search"
                    placeholder="Nombre o correo electrónico"
                />
                <BaseSelect
                    v-model="roleFilter"
                    label="Filtrar por rol"
                    :options="roleOptions"
                />
            </div>
        </BaseCard>

        <BaseCard v-if="loading" :padded="false" class="overflow-hidden">
            <div class="grid gap-0 divide-y divide-line">
                <div
                    v-for="row in 5"
                    :key="row"
                    class="grid grid-cols-[minmax(12rem,1.5fr)_minmax(13rem,1.5fr)_10rem_8rem] gap-5 px-4 py-4 @max-[620px]/content:grid-cols-1 @max-[620px]/content:gap-2"
                >
                    <BaseSkeleton shape="text" class="w-3/4" />
                    <BaseSkeleton shape="text" class="w-4/5" />
                    <BaseSkeleton shape="text" class="w-2/3" />
                    <BaseSkeleton shape="text" class="w-full" />
                </div>
            </div>
        </BaseCard>

        <EmptyState
            v-else-if="loadError"
            title="No pudimos cargar los usuarios"
            :description="loadError"
        >
            <template #icon>
                <AppIcon name="retry" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton variant="secondary" @click="loadUsers(1)">
                    Reintentar
                </BaseButton>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="users.data.length === 0"
            title="No encontramos usuarios"
            :description="
                search || roleFilter
                    ? 'Prueba con otra búsqueda o limpia el filtro de rol.'
                    : 'Crea el primer usuario para empezar a distribuir accesos.'
            "
        >
            <template #icon>
                <AppIcon name="users" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton v-if="!search && !roleFilter" @click="openCreate">
                    Crear usuario
                </BaseButton>
                <BaseButton
                    v-else
                    variant="secondary"
                    @click="
                        search = '';
                        roleFilter = '';
                    "
                >
                    Limpiar filtros
                </BaseButton>
            </template>
        </EmptyState>

        <template v-else>
            <BaseCard :padded="false" class="overflow-hidden">
                <!-- Tabla → tarjetas: bajo 620px de /content (el contenedor
                     nombrado de AppLayout, no el viewport) cada fila colapsa
                     en una tarjeta con etiquetas (data-label vía
                     before:content-[attr(...)]) en vez de forzar scroll
                     lateral. Mismo umbral que el mockup de referencia. -->
                <div
                    class="overflow-x-auto overscroll-x-contain @max-[620px]/content:overflow-visible"
                >
                    <table
                        class="w-full text-left text-sm @max-[620px]/content:block"
                    >
                        <thead
                            class="border-b border-line bg-surface-subtle text-ink-muted @max-[620px]/content:hidden"
                        >
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nombre</th>
                                <th class="px-4 py-3 font-semibold">Correo</th>
                                <th class="px-4 py-3 font-semibold">Roles</th>
                                <th class="px-4 py-3 font-semibold">Alta</th>
                                <th class="px-4 py-3 text-right font-semibold">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-line @max-[620px]/content:block @max-[620px]/content:divide-y-0 @max-[620px]/content:p-3"
                        >
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="bg-surface transition-colors hover:bg-surface-subtle @max-[620px]/content:mb-3 @max-[620px]/content:block @max-[620px]/content:rounded-card @max-[620px]/content:border @max-[620px]/content:border-line @max-[620px]/content:shadow-card @max-[620px]/content:last:mb-0"
                            >
                                <td
                                    data-label="Nombre"
                                    class="px-4 py-3 @max-[620px]/content:flex @max-[620px]/content:flex-col @max-[620px]/content:gap-1 @max-[620px]/content:border-b @max-[620px]/content:border-line @max-[620px]/content:px-3 @max-[620px]/content:py-2 @max-[620px]/content:before:text-[0.6875rem] @max-[620px]/content:before:font-bold @max-[620px]/content:before:tracking-[0.08em] @max-[620px]/content:before:text-ink-subtle @max-[620px]/content:before:uppercase @max-[620px]/content:before:content-[attr(data-label)]"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-primary-subtle text-xs font-bold text-primary"
                                        >
                                            {{
                                                user.name
                                                    .slice(0, 2)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-semibold text-ink"
                                            >
                                                {{ user.name }}
                                            </p>
                                            <p
                                                v-if="isCurrentUser(user)"
                                                class="text-xs text-ink-subtle"
                                            >
                                                Tu cuenta
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    data-label="Correo"
                                    class="px-4 py-3 text-ink-muted @max-[620px]/content:flex @max-[620px]/content:flex-col @max-[620px]/content:gap-1 @max-[620px]/content:border-b @max-[620px]/content:border-line @max-[620px]/content:px-3 @max-[620px]/content:py-2 @max-[620px]/content:before:text-[0.6875rem] @max-[620px]/content:before:font-bold @max-[620px]/content:before:tracking-[0.08em] @max-[620px]/content:before:text-ink-subtle @max-[620px]/content:before:uppercase @max-[620px]/content:before:content-[attr(data-label)]"
                                >
                                    {{ user.email }}
                                </td>
                                <td
                                    data-label="Roles"
                                    class="px-4 py-3 @max-[620px]/content:flex @max-[620px]/content:flex-col @max-[620px]/content:gap-1 @max-[620px]/content:border-b @max-[620px]/content:border-line @max-[620px]/content:px-3 @max-[620px]/content:py-2 @max-[620px]/content:before:text-[0.6875rem] @max-[620px]/content:before:font-bold @max-[620px]/content:before:tracking-[0.08em] @max-[620px]/content:before:text-ink-subtle @max-[620px]/content:before:uppercase @max-[620px]/content:before:content-[attr(data-label)]"
                                >
                                    <div class="flex flex-wrap gap-1.5">
                                        <BaseTag
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            :tone="
                                                role.name === 'admin'
                                                    ? 'primary'
                                                    : 'neutral'
                                            "
                                        >
                                            {{ role.name }}
                                        </BaseTag>
                                    </div>
                                </td>
                                <td
                                    data-label="Alta"
                                    class="px-4 py-3 font-data text-xs text-ink-muted tabular-nums @max-[620px]/content:flex @max-[620px]/content:flex-col @max-[620px]/content:gap-1 @max-[620px]/content:border-b @max-[620px]/content:border-line @max-[620px]/content:px-3 @max-[620px]/content:py-2 @max-[620px]/content:before:text-[0.6875rem] @max-[620px]/content:before:font-bold @max-[620px]/content:before:tracking-[0.08em] @max-[620px]/content:before:text-ink-subtle @max-[620px]/content:before:uppercase @max-[620px]/content:before:content-[attr(data-label)]"
                                >
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td
                                    data-label="Acciones"
                                    class="px-4 py-3 @max-[620px]/content:flex @max-[620px]/content:flex-col @max-[620px]/content:gap-1 @max-[620px]/content:px-3 @max-[620px]/content:py-2 @max-[620px]/content:before:text-[0.6875rem] @max-[620px]/content:before:font-bold @max-[620px]/content:before:tracking-[0.08em] @max-[620px]/content:before:text-ink-subtle @max-[620px]/content:before:uppercase @max-[620px]/content:before:content-[attr(data-label)]"
                                >
                                    <div
                                        class="flex justify-end gap-1 @max-[620px]/content:justify-start"
                                    >
                                        <BaseButton
                                            size="sm"
                                            variant="quiet"
                                            @click="openEdit(user)"
                                        >
                                            Editar
                                        </BaseButton>
                                        <BaseButton
                                            size="sm"
                                            variant="quiet"
                                            :disabled="isCurrentUser(user)"
                                            :title="
                                                isCurrentUser(user)
                                                    ? 'No puedes borrar tu propia cuenta'
                                                    : `Eliminar a ${user.name}`
                                            "
                                            class="text-error hover:bg-error-surface hover:text-error"
                                            @click="deleteTarget = user"
                                        >
                                            Borrar
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>

            <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted"
            >
                <p class="font-data tabular-nums">
                    {{ users.from }}–{{ users.to }} de
                    {{ users.total }} usuarios
                </p>
                <div class="flex gap-2">
                    <BaseButton
                        size="sm"
                        variant="secondary"
                        :disabled="users.current_page <= 1"
                        @click="loadUsers(users.current_page - 1)"
                    >
                        Anterior
                    </BaseButton>
                    <BaseButton
                        size="sm"
                        variant="secondary"
                        :disabled="users.current_page >= users.last_page"
                        @click="loadUsers(users.current_page + 1)"
                    >
                        Siguiente
                    </BaseButton>
                </div>
            </div>
        </template>

        <BaseModal
            :open="modalOpen"
            :title="editingUser ? 'Editar usuario' : 'Crear usuario'"
            :description="
                editingUser
                    ? 'Actualiza sus datos y el nivel de acceso.'
                    : 'Define sus credenciales iniciales y al menos un rol.'
            "
            class="sm:max-w-xl"
            :close-on-backdrop="!saving"
            @close="!saving && (modalOpen = false)"
        >
            <form id="user-form" class="grid gap-4" @submit.prevent="saveUser">
                <BaseInput
                    v-model="form.name"
                    label="Nombre"
                    autocomplete="name"
                    :error="firstError('name')"
                />
                <BaseInput
                    v-model="form.email"
                    label="Correo electrónico"
                    type="email"
                    autocomplete="email"
                    :error="firstError('email')"
                />
                <BaseInput
                    v-model="form.password"
                    label="Contraseña"
                    type="password"
                    :optional="editingUser !== null"
                    :hint="
                        editingUser
                            ? 'Déjala vacía para conservar la contraseña actual.'
                            : 'Usa una contraseña segura para el primer acceso.'
                    "
                    :autocomplete="
                        editingUser ? 'new-password' : 'new-password'
                    "
                    :error="firstError('password')"
                />

                <fieldset
                    class="grid gap-2"
                    :aria-invalid="rolesError() ? 'true' : undefined"
                    :aria-describedby="
                        rolesError() ? 'user-roles-error' : undefined
                    "
                >
                    <legend class="text-sm font-semibold text-ink">
                        Roles
                    </legend>
                    <div
                        class="grid gap-2 rounded-card border border-line bg-surface-subtle p-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex min-h-11 cursor-pointer items-center gap-3 rounded-control px-2 text-sm text-ink hover:bg-surface"
                        >
                            <input
                                type="checkbox"
                                :checked="form.roles.includes(role.name)"
                                class="h-4 w-4 rounded border-line-strong accent-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                                @change="toggleRole(role.name)"
                            />
                            <span class="font-medium">{{ role.name }}</span>
                        </label>
                    </div>
                    <AnimatePresence>
                        <motion.p
                            v-if="rolesError()"
                            id="user-roles-error"
                            class="text-xs font-medium text-error"
                            role="alert"
                            :initial="{ opacity: 0, y: -4 }"
                            :animate="{ opacity: 1, y: 0 }"
                            :exit="{ opacity: 0 }"
                            :transition="{ duration: 0.15 }"
                        >
                            {{ rolesError() }}
                        </motion.p>
                    </AnimatePresence>
                </fieldset>
            </form>

            <template #footer>
                <BaseButton
                    variant="quiet"
                    :disabled="saving"
                    @click="modalOpen = false"
                >
                    Cancelar
                </BaseButton>
                <BaseButton
                    type="submit"
                    form="user-form"
                    :loading="saving"
                    loading-label="Guardando usuario"
                >
                    {{ editingUser ? 'Guardar cambios' : 'Crear usuario' }}
                </BaseButton>
            </template>
        </BaseModal>

        <BaseModal
            :open="deleteTarget !== null"
            title="Eliminar usuario"
            :description="
                deleteTarget
                    ? `Se eliminará la cuenta de ${deleteTarget.name}. Esta acción no se puede deshacer.`
                    : undefined
            "
            :close-on-backdrop="!deleting"
            @close="!deleting && (deleteTarget = null)"
        >
            <p class="text-sm leading-6 text-ink-muted">
                Sus roles y permisos dejarán de estar disponibles
                inmediatamente.
            </p>
            <template #footer>
                <BaseButton
                    variant="quiet"
                    :disabled="deleting"
                    @click="deleteTarget = null"
                >
                    Cancelar
                </BaseButton>
                <BaseButton
                    variant="danger"
                    :loading="deleting"
                    loading-label="Eliminando usuario"
                    @click="deleteUser"
                >
                    Eliminar usuario
                </BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
