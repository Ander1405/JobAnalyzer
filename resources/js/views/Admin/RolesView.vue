<script setup lang="ts">
import { AnimatePresence, motion } from 'motion-v';
import { computed, onMounted, reactive, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseButton,
    BaseCard,
    BaseInput,
    BaseModal,
    BaseSkeleton,
    BaseTag,
    EmptyState,
} from '@/components/ui';
import { useToast } from '@/lib/toast';
import type {
    AdminPermission,
    AdminRole,
    ApiValidationErrors,
    RoleIndexResponse,
} from '@/types/admin';

type ApiFailure = {
    message?: string;
    errors?: ApiValidationErrors;
};

const PROTECTED_ROLES = ['admin', 'user'];
const PERMISSION_LABELS: Record<string, string> = {
    'users.view': 'Ver usuarios',
    'users.create': 'Crear usuarios',
    'users.update': 'Editar usuarios',
    'users.delete': 'Eliminar usuarios',
    'roles.manage': 'Gestionar roles',
};

const toast = useToast();
const roles = ref<AdminRole[]>([]);
const permissions = ref<AdminPermission[]>([]);
const loading = ref(true);
const loadError = ref<string | null>(null);
const modalOpen = ref(false);
const editingRole = ref<AdminRole | null>(null);
const saving = ref(false);
const formErrors = ref<ApiValidationErrors>({});
const deleteTarget = ref<AdminRole | null>(null);
const deleting = ref(false);
const form = reactive({
    name: '',
    permissions: [] as string[],
});

const permissionGroups = computed(() => [
    {
        label: 'Usuarios',
        permissions: permissions.value.filter((permission) =>
            permission.name.startsWith('users.'),
        ),
    },
    {
        label: 'Roles y permisos',
        permissions: permissions.value.filter((permission) =>
            permission.name.startsWith('roles.'),
        ),
    },
]);

onMounted(loadRoles);

async function loadRoles(): Promise<void> {
    loading.value = true;
    loadError.value = null;

    try {
        const response = await fetch('/api/admin/roles', {
            headers: { Accept: 'application/json' },
        });
        const data = await readResponse<RoleIndexResponse>(response);
        roles.value = data.data;
        permissions.value = data.permissions;
    } catch (error) {
        loadError.value = failureMessage(
            error,
            'No se pudieron cargar los roles y permisos.',
        );
        toast.error(loadError.value);
    } finally {
        loading.value = false;
    }
}

function openCreate(): void {
    editingRole.value = null;
    form.name = '';
    form.permissions = [];
    formErrors.value = {};
    modalOpen.value = true;
}

function openEdit(role: AdminRole): void {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = role.permissions.map((permission) => permission.name);
    formErrors.value = {};
    modalOpen.value = true;
}

function togglePermission(permissionName: string): void {
    const index = form.permissions.indexOf(permissionName);

    if (index === -1) {
        form.permissions.push(permissionName);
    } else {
        form.permissions.splice(index, 1);
    }
}

async function saveRole(): Promise<void> {
    saving.value = true;
    formErrors.value = {};

    const endpoint = editingRole.value
        ? `/api/admin/roles/${editingRole.value.id}`
        : '/api/admin/roles';

    try {
        const response = await fetch(endpoint, {
            method: editingRole.value ? 'PUT' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(form),
        });
        await readResponse<AdminRole>(response);

        const wasEditing = editingRole.value !== null;
        modalOpen.value = false;
        toast.success(wasEditing ? 'Rol actualizado.' : 'Rol creado.');
        await loadRoles();
    } catch (error) {
        const failure = error as ApiFailure;
        formErrors.value = failure.errors ?? {};

        if (!failure.errors) {
            toast.error(failureMessage(error, 'No se pudo guardar el rol.'));
        }
    } finally {
        saving.value = false;
    }
}

async function deleteRole(): Promise<void> {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    try {
        const response = await fetch(
            `/api/admin/roles/${deleteTarget.value.id}`,
            {
                method: 'DELETE',
                headers: { Accept: 'application/json' },
            },
        );
        await readResponse<null>(response);
        deleteTarget.value = null;
        toast.success('Rol eliminado.');
        await loadRoles();
    } catch (error) {
        toast.error(failureMessage(error, 'No se pudo eliminar el rol.'));
    } finally {
        deleting.value = false;
    }
}

function isProtected(role: AdminRole): boolean {
    return PROTECTED_ROLES.includes(role.name);
}

function permissionLabel(permissionName: string): string {
    return PERMISSION_LABELS[permissionName] ?? permissionName;
}

function firstError(field: string): string | undefined {
    return formErrors.value[field]?.[0];
}

function permissionsError(): string | undefined {
    const key = Object.keys(formErrors.value).find(
        (field) => field === 'permissions' || field.startsWith('permissions.'),
    );

    return key ? formErrors.value[key]?.[0] : undefined;
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
                        Roles y permisos
                    </h1>
                    <p
                        class="mt-1 max-w-2xl text-step-body leading-6 text-ink-muted"
                    >
                        Define responsabilidades con permisos explícitos y
                        revisa cuántas personas usan cada rol.
                    </p>
                </div>

                <BaseButton @click="openCreate">
                    <template #leading>
                        <AppIcon name="roles" class="h-4 w-4" />
                    </template>
                    Crear rol
                </BaseButton>
            </div>

            <nav
                class="mt-5 flex gap-1"
                aria-label="Secciones de administración"
            >
                <router-link
                    to="/admin/users"
                    class="rounded-control px-3 py-2 text-sm font-semibold text-ink-muted hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                >
                    Usuarios
                </router-link>
                <router-link
                    to="/admin/roles"
                    class="rounded-control bg-primary-subtle px-3 py-2 text-sm font-semibold text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                    aria-current="page"
                >
                    Roles y permisos
                </router-link>
            </nav>
        </header>

        <!-- Grid por @container/content (convención de AppLayout) en vez de
             breakpoints de viewport: reacciona al ancho real del panel, no
             al de la ventana, así el sidebar puede angostar/ensanchar el
             contenido sin dejar columnas apretadas. -->
        <div
            v-if="loading"
            class="grid gap-4 @lg/content:grid-cols-2 @4xl/content:grid-cols-3"
            aria-label="Cargando roles"
        >
            <BaseCard v-for="item in 3" :key="item" class="grid gap-4">
                <div class="flex items-center justify-between gap-3">
                    <BaseSkeleton shape="text" class="w-1/3" />
                    <BaseSkeleton shape="text" class="w-16" />
                </div>
                <BaseSkeleton class="h-20 w-full" />
                <BaseSkeleton shape="text" class="w-2/3" />
            </BaseCard>
        </div>

        <EmptyState
            v-else-if="loadError"
            title="No pudimos cargar los roles"
            :description="loadError"
        >
            <template #icon>
                <AppIcon name="retry" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton variant="secondary" @click="loadRoles">
                    Reintentar
                </BaseButton>
            </template>
        </EmptyState>

        <EmptyState
            v-else-if="roles.length === 0"
            title="Todavía no hay roles"
            description="Crea un rol para agrupar permisos y asignarlo a tu equipo."
        >
            <template #icon>
                <AppIcon name="roles" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton @click="openCreate">Crear rol</BaseButton>
            </template>
        </EmptyState>

        <section
            v-else
            class="grid gap-4 @lg/content:grid-cols-2 @4xl/content:grid-cols-3"
            aria-label="Roles disponibles"
        >
            <BaseCard
                v-for="role in roles"
                :key="role.id"
                variant="interactive"
                class="flex min-h-64 flex-col"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="truncate text-lg font-semibold tracking-[-0.02em] text-ink"
                            >
                                {{ role.name }}
                            </h2>
                            <BaseTag v-if="isProtected(role)" tone="primary">
                                Rol base
                            </BaseTag>
                        </div>
                        <p class="mt-1 text-xs text-ink-subtle">
                            {{ role.users_count }}
                            {{
                                role.users_count === 1 ? 'usuario' : 'usuarios'
                            }}
                        </p>
                    </div>
                    <span
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-card bg-primary-subtle text-primary"
                    >
                        <AppIcon name="roles" class="h-5 w-5" />
                    </span>
                </div>

                <div class="mt-5 flex-1 border-t border-line pt-4">
                    <p
                        class="mb-2 text-[0.6875rem] font-bold tracking-[0.1em] text-ink-subtle uppercase"
                    >
                        Permisos
                    </p>
                    <div
                        v-if="role.permissions.length > 0"
                        class="flex flex-wrap gap-1.5"
                    >
                        <BaseTag
                            v-for="permission in role.permissions"
                            :key="permission.id"
                            tone="neutral"
                        >
                            {{ permissionLabel(permission.name) }}
                        </BaseTag>
                    </div>
                    <p v-else class="text-sm leading-6 text-ink-muted">
                        Sin permisos asignados.
                    </p>
                </div>

                <div
                    class="mt-5 flex justify-end gap-1 border-t border-line pt-3"
                >
                    <BaseButton
                        size="sm"
                        variant="quiet"
                        @click="openEdit(role)"
                    >
                        Editar
                    </BaseButton>
                    <BaseButton
                        size="sm"
                        variant="quiet"
                        :disabled="isProtected(role) || role.users_count > 0"
                        :title="
                            isProtected(role)
                                ? 'Los roles base no se pueden eliminar'
                                : role.users_count > 0
                                  ? 'Desasigna este rol antes de eliminarlo'
                                  : `Eliminar el rol ${role.name}`
                        "
                        class="text-error hover:bg-error-surface hover:text-error"
                        @click="deleteTarget = role"
                    >
                        Borrar
                    </BaseButton>
                </div>
            </BaseCard>
        </section>

        <BaseModal
            :open="modalOpen"
            :title="editingRole ? 'Editar rol' : 'Crear rol'"
            :description="
                editingRole?.name === 'admin'
                    ? 'El nombre y los permisos administrativos son obligatorios.'
                    : editingRole && isProtected(editingRole)
                      ? 'El nombre del rol base está protegido; sus permisos sí se pueden actualizar.'
                      : 'Usa un nombre técnico breve y selecciona los permisos necesarios.'
            "
            class="sm:max-w-xl"
            :close-on-backdrop="!saving"
            @close="!saving && (modalOpen = false)"
        >
            <form id="role-form" class="grid gap-5" @submit.prevent="saveRole">
                <BaseInput
                    v-model="form.name"
                    label="Nombre del rol"
                    placeholder="recruiter"
                    hint="Solo minúsculas, números, guiones y guiones bajos."
                    :disabled="editingRole !== null && isProtected(editingRole)"
                    :error="firstError('name')"
                />

                <fieldset
                    class="grid gap-3"
                    :aria-invalid="permissionsError() ? 'true' : undefined"
                    :aria-describedby="
                        permissionsError()
                            ? 'role-permissions-error'
                            : undefined
                    "
                >
                    <legend class="text-sm font-semibold text-ink">
                        Permisos
                    </legend>
                    <div class="grid gap-3">
                        <section
                            v-for="group in permissionGroups"
                            :key="group.label"
                            class="rounded-card border border-line bg-surface-subtle p-3"
                        >
                            <h3
                                class="mb-2 text-xs font-bold tracking-[0.08em] text-ink-subtle uppercase"
                            >
                                {{ group.label }}
                            </h3>
                            <div class="grid gap-1 sm:grid-cols-2">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission.id"
                                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-control px-2 text-sm text-ink hover:bg-surface"
                                    :class="
                                        editingRole?.name === 'admin' &&
                                        'cursor-not-allowed opacity-60'
                                    "
                                >
                                    <input
                                        type="checkbox"
                                        :checked="
                                            form.permissions.includes(
                                                permission.name,
                                            )
                                        "
                                        class="h-4 w-4 rounded border-line-strong accent-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                                        :disabled="
                                            editingRole?.name === 'admin'
                                        "
                                        @change="
                                            togglePermission(permission.name)
                                        "
                                    />
                                    <span>{{
                                        permissionLabel(permission.name)
                                    }}</span>
                                </label>
                            </div>
                        </section>
                    </div>
                    <AnimatePresence>
                        <motion.p
                            v-if="permissionsError()"
                            id="role-permissions-error"
                            class="text-xs font-medium text-error"
                            role="alert"
                            :initial="{ opacity: 0, y: -4 }"
                            :animate="{ opacity: 1, y: 0 }"
                            :exit="{ opacity: 0 }"
                            :transition="{ duration: 0.15 }"
                        >
                            {{ permissionsError() }}
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
                    form="role-form"
                    :loading="saving"
                    loading-label="Guardando rol"
                >
                    {{ editingRole ? 'Guardar cambios' : 'Crear rol' }}
                </BaseButton>
            </template>
        </BaseModal>

        <BaseModal
            :open="deleteTarget !== null"
            title="Eliminar rol"
            :description="
                deleteTarget
                    ? `Se eliminará el rol ${deleteTarget.name}. Esta acción no se puede deshacer.`
                    : undefined
            "
            :close-on-backdrop="!deleting"
            @close="!deleting && (deleteTarget = null)"
        >
            <p class="text-sm leading-6 text-ink-muted">
                Las personas que lo tengan asignado perderán esos permisos.
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
                    loading-label="Eliminando rol"
                    @click="deleteRole"
                >
                    Eliminar rol
                </BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
