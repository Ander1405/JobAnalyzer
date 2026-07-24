export type AdminPermission = {
    id: number;
    name: string;
};

export type AdminRole = {
    id: number;
    name: string;
    permissions: AdminPermission[];
    users_count: number;
    created_at: string;
    updated_at: string;
};

export type AdminUserRole = {
    id: number;
    name: string;
};

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    roles: AdminUserRole[];
    created_at: string;
    updated_at: string;
};

export type PaginatedAdminUsers = {
    data: AdminUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type RoleIndexResponse = {
    data: AdminRole[];
    permissions: AdminPermission[];
};

export type ApiValidationErrors = Record<string, string[]>;
