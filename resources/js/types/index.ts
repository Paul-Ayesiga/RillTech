export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
    active?: boolean;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    group_id: number | null;
    created_at: string;
    updated_at: string;
    group?: PermissionGroup;
    roles?: Role[];
}

export interface PermissionGroup {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    display_order: number;
    created_at: string;
    updated_at: string;
    permissions?: Permission[];
    permissions_count?: number;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    permissions?: Permission[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: any;
}
