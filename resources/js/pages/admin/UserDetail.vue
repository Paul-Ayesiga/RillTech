<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type User, type Role, type Permission } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';

// Icons
import { ArrowLeft, User as UserIcon, Mail, Calendar, ShieldCheck, Check, X, Save, Info } from 'lucide-vue-next';

const props = defineProps({
  user: Object as () => User,
  allRoles: Array as () => Role[],
  allPermissions: Array as () => Permission[],
});

// Form for updating user roles and permissions
const form = useForm({
  roles: props.user.roles?.map((role: Role) => role.id) || [],
  permissions: [] as number[], // Direct permissions (not through roles)
});

// State
const isEditing = ref(false);
const isSaving = ref(false);

// Computed properties
const userRoleIds = computed(() => props.user.roles?.map((role: Role) => role.id) || []);
const userPermissionIds = computed(() => {
  // If user has super-admin role, they have ALL permissions (via Gate::before in AuthServiceProvider)
  if (hasSuperAdminRole.value) {
    return props.allPermissions?.map((perm: Permission) => perm.id) || [];
  }

  // Get all permissions from user's roles (excluding super-admin since it gets permissions via Gate)
  const rolePermissions = props.user.roles?.flatMap((role: Role) => {
    // Skip super-admin role as it gets permissions through AuthServiceProvider Gate::before
    if (role.name === 'super-admin') {
      return [];
    }
    return role.permissions?.map((perm: Permission) => perm.id) || [];
  }) || [];

  // Add direct permissions (if any)
  const directPermissions = props.user.permissions?.map((perm: Permission) => perm.id) || [];

  return [...new Set([...rolePermissions, ...directPermissions])];
});

const hasSuperAdminRole = computed(() =>
  props.user.roles?.some((role: Role) => role.name === 'super-admin') || false
);

// Computed property for safe email handling
const userEmail = computed(() => {
  const email = props.user?.email;
  if (!email) return '';

  // Ensure email is properly trimmed and formatted
  return email.toString().trim();
});

const mailtoLink = computed(() => {
  const email = userEmail.value;
  if (!email) return '#';

  return `mailto:${email}`;
});

// Check if user has a specific role
const hasRole = (roleId: number) => {
  return userRoleIds.value.includes(roleId);
};

// Check if user has a specific permission (through roles or direct)
const hasPermission = (permissionId: number) => {
  return userPermissionIds.value.includes(permissionId);
};

// Check if role is super-admin
const isSuperAdminRole = (roleName: string) => {
  return roleName === 'super-admin';
};

// Toggle role
const toggleRole = (roleId: number) => {
  if (!isEditing.value) return;

  const index = form.roles.indexOf(roleId);
  if (index === -1) {
    form.roles.push(roleId);
  } else {
    form.roles.splice(index, 1);
  }
};

// Save changes
const saveChanges = () => {
  isSaving.value = true;

  form.put(route('admin.users.update-roles-permissions', props.user.id), {
    onSuccess: () => {
      toast.success('User roles and permissions updated successfully');
      isEditing.value = false;
      isSaving.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to update user roles and permissions');
      console.error(errors);
      isSaving.value = false;
    },
  });
};

// Cancel editing
const cancelEditing = () => {
  isEditing.value = false;
  // Reset form to original values
  form.roles = props.user.roles?.map((role: Role) => role.id) || [];
  form.permissions = [];
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard',
  },
  {
    title: 'User Management',
    href: '/admin/users',
  },
  {
    title: props.user?.name || 'User Details',
    href: `/admin/users/${props.user?.id}`,
  },
];

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Get role badge color
const getRoleBadgeColor = (roleName: string) => {
  switch (roleName) {
    case 'super-admin':
      return 'bg-primary/10 text-primary border-primary/20';
    case 'admin':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800/30';
    case 'client':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800/30';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800/30';
  }
};
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <Head :title="`User: ${user?.name}`" />

    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center gap-4">
        <Button as-child variant="outline" size="icon">
          <Link :href="route('admin.users.index')">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <h1 class="text-2xl font-bold">User Details</h1>
      </div>

      <div class="grid gap-6 md:grid-cols-3">
        <!-- User Information -->
        <Card class="md:col-span-1">
          <CardHeader>
            <CardTitle>User Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-col items-center text-center mb-6">
              <div class="h-24 w-24 rounded-full bg-muted flex items-center justify-center mb-4">
                <UserIcon class="h-12 w-12 text-muted-foreground" />
              </div>
              <h3 class="text-xl font-semibold">{{ user.name }}</h3>
              <p class="text-muted-foreground">{{ user.email }}</p>

              <div class="flex flex-wrap justify-center gap-1 mt-2">
                <span
                  v-for="role in user.roles"
                  :key="role.id"
                  class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                  :class="getRoleBadgeColor(role.name)"
                >
                  <ShieldCheck v-if="role.name === 'super-admin'" class="mr-1 h-3 w-3" />
                  {{ role.name }}
                </span>
              </div>
            </div>

            <div class="space-y-4">
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">ID</span>
                <span class="font-medium">{{ user.id }}</span>
              </div>
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">Email Verified</span>
                <span v-if="user.email_verified_at" class="text-green-600 dark:text-green-400 font-medium">
                  <Check class="inline h-4 w-4 mr-1" />
                  {{ formatDate(user.email_verified_at) }}
                </span>
                <span v-else class="text-amber-600 dark:text-amber-400 font-medium">
                  <X class="inline h-4 w-4 mr-1" />
                  Not verified
                </span>
              </div>
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">Created</span>
                <span class="font-medium">{{ formatDate(user.created_at) }}</span>
              </div>
              <div class="flex justify-between pb-2">
                <span class="text-muted-foreground">Last Updated</span>
                <span class="font-medium">{{ formatDate(user.updated_at) }}</span>
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <div class="flex w-full gap-2">
              <Button as-child variant="outline" class="flex-1">
                <Link :href="route('admin.users.edit', user.id)" class="flex items-center justify-center">
                  Edit User
                </Link>
              </Button>
              <Button as-child variant="default" class="flex-1" :disabled="!userEmail">
                <a :href="mailtoLink" class="flex items-center justify-center">
                  <Mail class="mr-2 h-4 w-4" />
                  Email
                </a>
              </Button>
            </div>
          </CardFooter>
        </Card>

        <!-- Roles and Permissions -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle>Roles & Permissions</CardTitle>
            <CardDescription>
              User's assigned roles and permissions
            </CardDescription>
          </CardHeader>
          <CardContent>
            <!-- Edit Mode Toggle -->
            <div class="mb-4 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold">Roles & Permissions</h3>
                <Badge v-if="hasSuperAdminRole" variant="default" class="text-xs">
                  <ShieldCheck class="h-3 w-3 mr-1" />
                  Super Admin
                </Badge>
              </div>
              <div class="flex items-center gap-2">
                <Button
                  v-if="!isEditing"
                  @click="isEditing = true"
                  variant="outline"
                  size="sm"
                >
                  <Save class="h-4 w-4 mr-2" />
                  Edit Roles
                </Button>
                <div v-else class="flex gap-2">
                  <Button
                    @click="saveChanges"
                    :disabled="isSaving"
                    variant="default"
                    size="sm"
                  >
                    <Save class="h-4 w-4 mr-2" />
                    {{ isSaving ? 'Saving...' : 'Save Changes' }}
                  </Button>
                  <Button
                    @click="cancelEditing"
                    variant="outline"
                    size="sm"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </div>

            <!-- Super Admin Notice -->
            <Alert v-if="hasSuperAdminRole" class="mb-4">
              <Info class="h-4 w-4" />
              <AlertDescription>
                This user has the <strong>super-admin</strong> role and automatically inherits all permissions in the system.
              </AlertDescription>
            </Alert>

            <Tabs defaultValue="roles" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="roles">Roles ({{ userRoleIds.length }})</TabsTrigger>
                <TabsTrigger value="permissions">Permissions ({{ userPermissionIds.length }})</TabsTrigger>
              </TabsList>

              <!-- Roles Tab -->
              <TabsContent value="roles" class="space-y-4">
                <!-- View Mode: Show only active roles -->
                <div v-if="!isEditing">
                  <div v-if="userRoleIds.length > 0" class="space-y-3">
                    <div v-for="role in user.roles" :key="role.id" class="flex items-start space-x-3 p-3 rounded-lg border bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800">
                      <div class="mt-1">
                        <Check class="h-4 w-4 text-green-600 dark:text-green-400" />
                      </div>
                      <div class="flex-1 space-y-1">
                        <div class="text-sm font-medium leading-none flex items-center gap-2">
                          <ShieldCheck v-if="isSuperAdminRole(role.name)" class="h-4 w-4 text-primary" />
                          {{ role.name }}
                          <Badge variant="default" class="text-xs">Active</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                          {{ role.guard_name }} • {{ isSuperAdminRole(role.name) ? (allPermissions?.length || 0) : (role.permissions?.length || 0) }} permissions
                        </p>
                        <div v-if="isSuperAdminRole(role.name)" class="flex flex-wrap gap-1 mt-2">
                          <Badge variant="outline" class="text-xs bg-blue-50 text-blue-700 border-blue-200">
                            All System Permissions
                          </Badge>
                        </div>
                        <div v-else-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1 mt-2">
                          <Badge
                            v-for="permission in role.permissions.slice(0, 3)"
                            :key="permission.id"
                            variant="outline"
                            class="text-xs"
                          >
                            {{ permission.name }}
                          </Badge>
                          <Badge
                            v-if="role.permissions.length > 3"
                            variant="outline"
                            class="text-xs"
                          >
                            +{{ role.permissions.length - 3 }} more
                          </Badge>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-8">
                    <p class="text-muted-foreground">No roles assigned to this user.</p>
                  </div>
                </div>

                <!-- Edit Mode: Show all roles with checkboxes -->
                <div v-else>
                  <div v-if="allRoles && allRoles.length > 0" class="space-y-3">
                    <div v-for="role in allRoles" :key="role.id" class="flex items-start space-x-3 p-3 rounded-lg border">
                      <input
                        :id="`role-${role.id}`"
                        type="checkbox"
                        :checked="form.roles.includes(role.id)"
                        @change="toggleRole(role.id)"
                        class="mt-1 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                      />
                      <div class="flex-1 space-y-1">
                        <label
                          :for="`role-${role.id}`"
                          class="text-sm font-medium leading-none cursor-pointer flex items-center gap-2"
                        >
                          <ShieldCheck v-if="isSuperAdminRole(role.name)" class="h-4 w-4 text-primary" />
                          {{ role.name }}
                          <Badge v-if="form.roles.includes(role.id)" variant="default" class="text-xs">Selected</Badge>
                        </label>
                        <p class="text-xs text-muted-foreground">
                          {{ role.guard_name }} • {{ isSuperAdminRole(role.name) ? (allPermissions?.length || 0) : (role.permissions?.length || 0) }} permissions
                        </p>
                        <div v-if="isSuperAdminRole(role.name)" class="flex flex-wrap gap-1 mt-2">
                          <Badge variant="outline" class="text-xs bg-blue-50 text-blue-700 border-blue-200">
                            All System Permissions
                          </Badge>
                        </div>
                        <div v-else-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1 mt-2">
                          <Badge
                            v-for="permission in role.permissions.slice(0, 3)"
                            :key="permission.id"
                            variant="outline"
                            class="text-xs"
                          >
                            {{ permission.name }}
                          </Badge>
                          <Badge
                            v-if="role.permissions.length > 3"
                            variant="outline"
                            class="text-xs"
                          >
                            +{{ role.permissions.length - 3 }} more
                          </Badge>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-8">
                    <p class="text-muted-foreground">No roles available in the system.</p>
                  </div>
                </div>
              </TabsContent>

              <!-- Permissions Tab -->
              <TabsContent value="permissions" class="space-y-4">
                <div v-if="hasSuperAdminRole" class="space-y-4">
                  <!-- Super Admin Notice -->
                  <div class="text-center py-4">
                    <div class="flex flex-col items-center gap-2">
                      <ShieldCheck class="h-12 w-12 text-primary" />
                      <h3 class="text-lg font-semibold">Super Admin Access</h3>
                      <p class="text-muted-foreground">This user has super admin privileges and automatically has access to all permissions.</p>
                    </div>
                  </div>

                  <!-- Show All Permissions for Super Admin -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                      v-for="permission in allPermissions"
                      :key="permission.id"
                      class="flex items-center space-x-2 p-3 rounded-lg border bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800"
                    >
                      <ShieldCheck class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                      <div class="flex-1">
                        <p class="text-sm font-medium">{{ permission.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ permission.guard_name }} • Super Admin</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else-if="userPermissionIds.length > 0" class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                      v-for="permission in allPermissions?.filter(p => hasPermission(p.id))"
                      :key="permission.id"
                      class="flex items-center space-x-2 p-3 rounded-lg border bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800"
                    >
                      <Check class="h-4 w-4 text-green-600 dark:text-green-400" />
                      <div class="flex-1">
                        <p class="text-sm font-medium">{{ permission.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ permission.guard_name }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-8">
                  <p class="text-muted-foreground">No permissions assigned. Assign roles to this user to grant permissions.</p>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
