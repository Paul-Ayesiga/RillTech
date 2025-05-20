<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type Role, type Permission as PermissionType } from '@/types';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// ScrollArea removed to fix scroll-linked positioning effect warning
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { PlusCircle, AlertTriangle, Info, ShieldCheck, ShieldAlert, LoaderCircle } from 'lucide-vue-next';
import DataTableRoles from '@/components/admin/DataTableRoles.vue';
import GroupedPermissions from '@/components/admin/GroupedPermissions.vue';

// Define breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Roles & Permissions',
        href: '/admin/roles-permissions',
    },
];

// Define props interface
interface Props {
    roles: Role[];
    permissions: PermissionType[];
    permissionGroups: {
        id: number;
        name: string;
        description: string | null;
        color: string | null;
        display_order: number;
        permissions: PermissionType[];
    }[];
    ungroupedPermissions: PermissionType[];
}

const props = defineProps<Props>();

// Role dialog state
const isRoleDialogOpen = ref(false);
const isEditingRole = ref(false);
const currentRoleId = ref<number | null>(null);
const roleToDelete = ref<Role | null>(null);
const isDeleteRoleDialogOpen = ref(false);
const isDeletingRole = ref(false);

// Permission dialog state
const isPermissionDialogOpen = ref(false);
const isEditingPermission = ref(false);
const currentPermissionId = ref<number | null>(null);
const permissionToDelete = ref<PermissionType | null>(null);
const isDeletePermissionDialogOpen = ref(false);
const isDeletingPermission = ref(false);

// Role form
const roleForm = useForm({
    name: '',
    permissions: [] as number[],
});

// Function to reset role form
const resetRoleForm = () => {
    roleForm.name = '';
    roleForm.permissions = [];
    roleForm.clearErrors();
};

// Permission form
const permissionForm = useForm({
    name: '',
    guard_name: 'web',
    group_id: null as number | null,
    roles: [] as number[],
});

// Function to reset permission form
const resetPermissionForm = () => {
    permissionForm.name = '';
    permissionForm.guard_name = 'web';
    permissionForm.group_id = null;
    permissionForm.roles = [];
    permissionForm.clearErrors();
};

// Open role dialog for creating a new role
const openCreateRoleDialog = () => {
    isEditingRole.value = false;
    currentRoleId.value = null;
    resetRoleForm();
    isRoleDialogOpen.value = true;
};

// Open role dialog for editing an existing role
const openEditRoleDialog = (role: Role) => {
    isEditingRole.value = true;
    currentRoleId.value = role.id;

    // Reset form first
    resetRoleForm();

    // Set form values
    roleForm.name = role.name;

    // Set selected permissions
    const selectedPermissionIds = role.permissions?.map((p: { id: number }) => p.id) || [];
    roleForm.permissions = [...selectedPermissionIds]; // Create a new array to ensure reactivity

    isRoleDialogOpen.value = true;
};

// Handle role dialog close
const handleRoleDialogClose = () => {
    resetRoleForm();
    isRoleDialogOpen.value = false;
};

// Submit role form
const submitRoleForm = () => {
    if (isEditingRole.value && currentRoleId.value) {
        roleForm.put(`/admin/roles/${currentRoleId.value}`, {
            onSuccess: () => {
                handleRoleDialogClose();
                toast.success('Role updated successfully');
            },
            onError: () => {
                toast.error('Failed to update role');
            }
        });
    } else {
        roleForm.post('/admin/roles', {
            onSuccess: () => {
                handleRoleDialogClose();
                toast.success('Role created successfully');
            },
            onError: () => {
                toast.error('Failed to create role');
            }
        });
    }
};

// Open delete role dialog
const openDeleteRoleDialog = (role: Role) => {
    roleToDelete.value = role;
    isDeleteRoleDialogOpen.value = true;
};

// Delete role
const deleteRole = () => {
    if (!roleToDelete.value) return;

    // Prevent deleting super-admin role
    if (roleToDelete.value.name === 'super-admin') {
        toast.error('Cannot delete the super-admin role');
        isDeleteRoleDialogOpen.value = false;
        return;
    }

    isDeletingRole.value = true;

    useForm({}).delete(`/admin/roles/${roleToDelete.value.id}`, {
        onSuccess: () => {
            toast.success('Role deleted successfully');
            isDeleteRoleDialogOpen.value = false;
            roleToDelete.value = null;
            isDeletingRole.value = false;
        },
        onError: () => {
            toast.error('Failed to delete role');
            isDeletingRole.value = false;
        }
    });
};

// Open permission dialog for creating a new permission
const openCreatePermissionDialog = () => {
    isEditingPermission.value = false;
    currentPermissionId.value = null;
    resetPermissionForm();

    // Find super-admin role and pre-select it
    const superAdmin = props.roles.find(role => role.name === 'super-admin');
    if (superAdmin) {
        permissionForm.roles.push(superAdmin.id);
    }

    isPermissionDialogOpen.value = true;
};

// Open permission dialog for editing an existing permission
const openEditPermissionDialog = (permission: PermissionType) => {
    isEditingPermission.value = true;
    currentPermissionId.value = permission.id;

    // Reset form first
    resetPermissionForm();

    // Set form values
    permissionForm.name = permission.name;
    permissionForm.guard_name = permission.guard_name;
    permissionForm.group_id = permission.group_id;

    // Set selected roles if available
    if (permission.roles && permission.roles.length > 0) {
        permissionForm.roles = permission.roles.map(role => role.id);
    }

    // Find super-admin role and ensure it's always included
    const superAdmin = props.roles.find(role => role.name === 'super-admin');
    if (superAdmin && !permissionForm.roles.includes(superAdmin.id)) {
        permissionForm.roles.push(superAdmin.id);
    }

    isPermissionDialogOpen.value = true;
};

// Handle permission dialog close
const handlePermissionDialogClose = () => {
    resetPermissionForm();
    isPermissionDialogOpen.value = false;
};

// Submit permission form
const submitPermissionForm = () => {
    if (isEditingPermission.value && currentPermissionId.value) {
        permissionForm.put(`/admin/permissions/${currentPermissionId.value}`, {
            onSuccess: () => {
                handlePermissionDialogClose();
                toast.success('Permission updated successfully');
            },
            onError: () => {
                toast.error('Failed to update permission');
            }
        });
    } else {
        permissionForm.post('/admin/permissions', {
            onSuccess: () => {
                handlePermissionDialogClose();
                toast.success('Permission created successfully');
            },
            onError: () => {
                toast.error('Failed to create permission');
            }
        });
    }
};

// Open delete permission dialog
const openDeletePermissionDialog = (permission: PermissionType) => {
    permissionToDelete.value = permission;
    isDeletePermissionDialogOpen.value = true;
};

// Delete permission
const deletePermission = () => {
    if (!permissionToDelete.value) return;

    isDeletingPermission.value = true;

    useForm({}).delete(`/admin/permissions/${permissionToDelete.value.id}`, {
        onSuccess: () => {
            toast.success('Permission deleted successfully');
            isDeletePermissionDialogOpen.value = false;
            permissionToDelete.value = null;
            isDeletingPermission.value = false;
        },
        onError: () => {
            toast.error('Failed to delete permission');
            isDeletingPermission.value = false;
        }
    });
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Roles & Permissions Management" />

        <Toaster />

        <div class="container max-w-7xl mx-auto px-4 py-8">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-2">
                    <ShieldCheck class="h-6 w-6 text-primary" />
                    <h1 class="text-3xl font-bold">Roles & Permissions Management</h1>
                </div>
                <p class="text-muted-foreground">Manage user roles and permissions for your application</p>
            </div>

            <Alert className="mb-6">
                <Info className="h-4 w-4" />
                <AlertTitle>Super Admin Role</AlertTitle>
                <AlertDescription>
                    The super-admin role automatically inherits all permissions in the system, including any new permissions that are created.
                </AlertDescription>
            </Alert>

            <Tabs default-value="roles" class="w-full">
                <TabsList class="grid w-full grid-cols-2 mb-8">
                    <TabsTrigger value="roles" class="text-base py-0">
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        Roles
                    </TabsTrigger>
                    <TabsTrigger value="permissions" class="text-base py-0">
                        <ShieldAlert class="mr-2 h-4 w-4" />
                        Permissions
                    </TabsTrigger>
                </TabsList>

                <!-- Roles Tab -->
                <TabsContent value="roles" class="mt-0">
                    <Card>
                        <CardHeader class="pb-4">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                <div>
                                    <CardTitle class="text-2xl">Roles</CardTitle>
                                    <CardDescription>Manage user roles in your application</CardDescription>
                                </div>
                                <Button @click="openCreateRoleDialog" size="sm" class="w-full sm:w-auto">
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Add Role
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <DataTableRoles
                                :roles="props.roles"
                                @edit="openEditRoleDialog"
                                @delete="openDeleteRoleDialog"
                            />
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Permissions Tab -->
                <TabsContent value="permissions" class="mt-0">
                    <Card>
                        <CardHeader class="pb-4">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                <div>
                                    <CardTitle class="text-2xl">Permissions</CardTitle>
                                    <CardDescription>Manage permissions in your application</CardDescription>
                                </div>
                                <Button @click="openCreatePermissionDialog" size="sm" class="w-full sm:w-auto">
                                    <PlusCircle class="mr-2 h-4 w-4" />
                                    Add Permission
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="mb-4">
                                <Alert>
                                    <Info class="h-4 w-4" />
                                    <AlertTitle>Permission Groups</AlertTitle>
                                    <AlertDescription>
                                        Permissions are organized by groups. You can manage permission groups from the
                                        <Link href="/admin/permission-groups" class="text-primary underline">Permission Groups</Link> page.
                                    </AlertDescription>
                                </Alert>
                            </div>
                            <GroupedPermissions
                                :permission-groups="props.permissionGroups"
                                :ungrouped-permissions="props.ungroupedPermissions"
                                @edit="openEditPermissionDialog"
                                @delete="openDeletePermissionDialog"
                            />
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Role Dialog -->
        <Dialog
            v-model:open="isRoleDialogOpen"
            @update:open="(open) => !open && handleRoleDialogClose()"
        >
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-xl">{{ isEditingRole ? 'Edit Role' : 'Create Role' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditingRole ? 'Update role details and permissions' : 'Add a new role to the system' }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitRoleForm">
                    <div class="grid gap-6 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="name" class="text-right font-medium">Name</Label>
                            <div class="col-span-3 space-y-1">
                                <Input
                                    id="name"
                                    v-model="roleForm.name"
                                    placeholder="Enter role name"
                                    class="w-full"
                                    :disabled="isEditingRole && roleForm.name === 'super-admin'"
                                />
                                <p v-if="roleForm.errors?.name" class="text-sm text-destructive">{{ roleForm.errors.name }}</p>
                            </div>
                        </div>
                        <Separator />
                        <div class="grid grid-cols-4 items-start gap-4">
                            <Label class="text-right font-medium pt-2">Permissions</Label>
                            <div class="col-span-3">
                                <div class="mb-2 text-sm text-muted-foreground">
                                    Select the permissions for this role
                                </div>
                                <div class="h-[200px] rounded-md border p-4 overflow-y-auto" scroll-region>
                                    <div class="grid gap-3">
                                        <div v-for="permission in props.permissions" :key="permission.id" class="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                :id="`permission-${permission.id}`"
                                                :value="permission.id"
                                                v-model="roleForm.permissions"
                                                :disabled="isEditingRole && roleForm.name === 'super-admin'"
                                                class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                            />
                                            <Label :for="`permission-${permission.id}`" class="text-sm">{{ permission.name }}</Label>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="roleForm.errors?.permissions" class="mt-1 text-sm text-destructive">{{ roleForm.errors.permissions }}</p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="handleRoleDialogClose"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="roleForm.processing || (isEditingRole && roleForm.name === 'super-admin')"
                        >
                            <LoaderCircle v-if="roleForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isEditingRole ? 'Update Role' : 'Create Role' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Permission Dialog -->
        <Dialog
            v-model:open="isPermissionDialogOpen"
            @update:open="(open) => !open && handlePermissionDialogClose()"
        >
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-xl">{{ isEditingPermission ? 'Edit Permission' : 'Create Permission' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditingPermission ? 'Update permission details' : 'Add a new permission to the system' }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitPermissionForm">
                    <div class="grid gap-6 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="permission-name" class="text-right font-medium">Name</Label>
                            <div class="col-span-3 space-y-1">
                                <Input
                                    id="permission-name"
                                    v-model="permissionForm.name"
                                    placeholder="Enter permission name"
                                    class="w-full"
                                />
                                <p v-if="permissionForm.errors?.name" class="text-sm text-destructive">{{ permissionForm.errors.name }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="guard-name" class="text-right font-medium">Guard</Label>
                            <div class="col-span-3 space-y-1">
                                <Input
                                    id="guard-name"
                                    v-model="permissionForm.guard_name"
                                    placeholder="web"
                                    class="w-full"
                                />
                                <p v-if="permissionForm.errors?.guard_name" class="text-sm text-destructive">{{ permissionForm.errors.guard_name }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="group-id" class="text-right font-medium">Group</Label>
                            <div class="col-span-3 space-y-1">
                                <select
                                    id="group-id"
                                    v-model="permissionForm.group_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option :value="null">No Group</option>
                                    <option v-for="group in props.permissionGroups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </option>
                                </select>
                                <p v-if="permissionForm.errors?.group_id" class="text-sm text-destructive">{{ permissionForm.errors.group_id }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-start gap-4">
                            <Label class="text-right font-medium pt-2">Assign to Roles</Label>
                            <div class="col-span-3">
                                <div class="mb-2 text-sm text-muted-foreground">
                                    Select the roles that should have this permission
                                </div>
                                <Alert variant="info" class="mb-2">
                                    <Info class="h-4 w-4" />
                                    <AlertDescription class="text-xs">
                                        The super-admin role always has all permissions and cannot be unchecked.
                                    </AlertDescription>
                                </Alert>
                                <div class="h-[150px] rounded-md border p-4 overflow-y-auto" scroll-region>
                                    <div class="grid gap-3">
                                        <div v-for="role in props.roles" :key="role.id" class="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                :id="`role-${role.id}`"
                                                :value="role.id"
                                                v-model="permissionForm.roles"
                                                :disabled="role.name === 'super-admin'"
                                                :checked="role.name === 'super-admin'"
                                                class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                            />
                                            <Label :for="`role-${role.id}`" class="text-sm">
                                                {{ role.name }}
                                                <span v-if="role.name === 'super-admin'" class="text-xs text-muted-foreground ml-1">
                                                    (always has all permissions)
                                                </span>
                                            </Label>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="permissionForm.errors?.roles" class="mt-1 text-sm text-destructive">{{ permissionForm.errors.roles }}</p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="handlePermissionDialogClose"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="permissionForm.processing"
                        >
                            <LoaderCircle v-if="permissionForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isEditingPermission ? 'Update Permission' : 'Create Permission' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Role Confirmation Dialog -->
        <Dialog
            v-model:open="isDeleteRoleDialogOpen"
            @update:open="(open) => !open && (roleToDelete = null)"
        >
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-xl flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-destructive" />
                        Confirm Delete Role
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the role "{{ roleToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Alert variant="destructive" class="mb-4">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertTitle>Warning</AlertTitle>
                        <AlertDescription>
                            Deleting this role will remove it from all users who currently have it assigned.
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteRoleDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deleteRole"
                        :disabled="isDeletingRole"
                    >
                        <LoaderCircle v-if="isDeletingRole" class="mr-2 h-4 w-4 animate-spin" />
                        Delete Role
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Permission Confirmation Dialog -->
        <Dialog
            v-model:open="isDeletePermissionDialogOpen"
            @update:open="(open) => !open && (permissionToDelete = null)"
        >
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-xl flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-destructive" />
                        Confirm Delete Permission
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the permission "{{ permissionToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Alert variant="destructive" class="mb-4">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertTitle>Warning</AlertTitle>
                        <AlertDescription>
                            Deleting this permission will remove it from all roles that currently have it assigned.
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeletePermissionDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deletePermission"
                        :disabled="isDeletingPermission"
                    >
                        <LoaderCircle v-if="isDeletingPermission" class="mr-2 h-4 w-4 animate-spin" />
                        Delete Permission
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
