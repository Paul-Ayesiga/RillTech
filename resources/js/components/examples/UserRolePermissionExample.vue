<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ShieldCheck, Save, Info, Check } from 'lucide-vue-next';

// Mock data for demonstration
const mockUser = ref({
  id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  roles: [
    {
      id: 1,
      name: 'admin',
      guard_name: 'web',
      permissions: [
        { id: 1, name: 'view users', guard_name: 'web' },
        { id: 2, name: 'create users', guard_name: 'web' },
        { id: 3, name: 'edit users', guard_name: 'web' },
        { id: 4, name: 'delete users', guard_name: 'web' },
      ]
    }
  ]
});

const mockAllRoles = ref([
  {
    id: 1,
    name: 'admin',
    guard_name: 'web',
    permissions: [
      { id: 1, name: 'view users', guard_name: 'web' },
      { id: 2, name: 'create users', guard_name: 'web' },
      { id: 3, name: 'edit users', guard_name: 'web' },
      { id: 4, name: 'delete users', guard_name: 'web' },
    ]
  },
  {
    id: 2,
    name: 'super-admin',
    guard_name: 'web',
    permissions: [
      { id: 1, name: 'view users', guard_name: 'web' },
      { id: 2, name: 'create users', guard_name: 'web' },
      { id: 3, name: 'edit users', guard_name: 'web' },
      { id: 4, name: 'delete users', guard_name: 'web' },
      { id: 5, name: 'manage roles', guard_name: 'web' },
      { id: 6, name: 'manage permissions', guard_name: 'web' },
      { id: 7, name: 'system settings', guard_name: 'web' },
    ]
  },
  {
    id: 3,
    name: 'client',
    guard_name: 'web',
    permissions: [
      { id: 8, name: 'view dashboard', guard_name: 'web' },
      { id: 9, name: 'manage profile', guard_name: 'web' },
    ]
  }
]);

const mockAllPermissions = ref([
  { id: 1, name: 'view users', guard_name: 'web' },
  { id: 2, name: 'create users', guard_name: 'web' },
  { id: 3, name: 'edit users', guard_name: 'web' },
  { id: 4, name: 'delete users', guard_name: 'web' },
  { id: 5, name: 'manage roles', guard_name: 'web' },
  { id: 6, name: 'manage permissions', guard_name: 'web' },
  { id: 7, name: 'system settings', guard_name: 'web' },
  { id: 8, name: 'view dashboard', guard_name: 'web' },
  { id: 9, name: 'manage profile', guard_name: 'web' },
]);

// State
const isEditing = ref(false);
const selectedRoles = ref([1]); // admin role selected by default

// Computed properties
const userRoleIds = computed(() => mockUser.value.roles?.map(role => role.id) || []);
const userPermissionIds = computed(() => {
  // If user has super-admin role, they have ALL permissions
  if (hasSuperAdminRole.value) {
    return mockAllPermissions.value.map(perm => perm.id);
  }

  const rolePermissions = mockUser.value.roles?.flatMap(role =>
    role.permissions?.map(perm => perm.id) || []
  ) || [];
  return [...new Set(rolePermissions)];
});

const hasSuperAdminRole = computed(() =>
  mockUser.value.roles?.some(role => role.name === 'super-admin') || false
);

// Functions
const hasRole = (roleId: number) => {
  return userRoleIds.value.includes(roleId);
};

const hasPermission = (permissionId: number) => {
  return userPermissionIds.value.includes(permissionId);
};

const isSuperAdminRole = (roleName: string) => {
  return roleName === 'super-admin';
};

const toggleRole = (roleId: number) => {
  if (!isEditing.value) return;

  const index = selectedRoles.value.indexOf(roleId);
  if (index === -1) {
    selectedRoles.value.push(roleId);
  } else {
    selectedRoles.value.splice(index, 1);
  }
};

const saveChanges = () => {
  // Simulate saving
  console.log('Saving roles:', selectedRoles.value);

  // Update mock user roles
  mockUser.value.roles = mockAllRoles.value.filter(role =>
    selectedRoles.value.includes(role.id)
  );

  isEditing.value = false;
  alert('Changes saved successfully!');
};

const cancelEditing = () => {
  isEditing.value = false;
  selectedRoles.value = [...userRoleIds.value];
};

const toggleSuperAdmin = () => {
  const superAdminRole = mockAllRoles.value.find(role => role.name === 'super-admin');
  if (superAdminRole) {
    if (mockUser.value.roles?.some(role => role.name === 'super-admin')) {
      // Remove super-admin
      mockUser.value.roles = mockUser.value.roles.filter(role => role.name !== 'super-admin');
    } else {
      // Add super-admin
      mockUser.value.roles = mockUser.value.roles || [];
      mockUser.value.roles.push(superAdminRole);
    }
  }
};
</script>

<template>
  <div class="px-3 space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>User Role & Permission Management</CardTitle>
        <CardDescription>
          Enhanced user detail page with interactive role and permission management, including super-admin handling.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Demo User Info -->
        <div class="p-4 bg-muted rounded-lg">
          <h4 class="font-semibold mb-2">Demo User: {{ mockUser.name }}</h4>
          <p class="text-sm text-muted-foreground">{{ mockUser.email }}</p>
          <div class="flex gap-2 mt-2">
            <Badge v-for="role in mockUser.roles" :key="role.id" variant="outline">
              {{ role.name }}
            </Badge>
          </div>
        </div>

        <!-- Demo Controls -->
        <div class="flex gap-2">
          <Button @click="toggleSuperAdmin" variant="outline" size="sm">
            {{ hasSuperAdminRole ? 'Remove' : 'Add' }} Super Admin
          </Button>
        </div>

        <!-- Enhanced Interface Demo -->
        <div class="border rounded-lg p-4">
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
                @click="isEditing = true; selectedRoles = [...userRoleIds]"
                variant="outline"
                size="sm"
              >
                <Save class="h-4 w-4 mr-2" />
                Edit Roles
              </Button>
              <div v-else class="flex gap-2">
                <Button
                  @click="saveChanges"
                  variant="default"
                  size="sm"
                >
                  <Save class="h-4 w-4 mr-2" />
                  Save Changes
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
                  <div v-for="role in mockUser.roles" :key="role.id" class="flex items-start space-x-3 p-3 rounded-lg border bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800">
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
                        {{ role.guard_name }} • {{ isSuperAdminRole(role.name) ? mockAllPermissions.length : (role.permissions?.length || 0) }} permissions
                      </p>
                      <div v-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1 mt-2">
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
                <div class="space-y-3">
                  <div v-for="role in mockAllRoles" :key="role.id" class="flex items-start space-x-3 p-3 rounded-lg border">
                    <input
                      :id="`role-${role.id}`"
                      type="checkbox"
                      :checked="selectedRoles.includes(role.id)"
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
                        <Badge v-if="selectedRoles.includes(role.id)" variant="default" class="text-xs">Selected</Badge>
                      </label>
                      <p class="text-xs text-muted-foreground">
                        {{ role.guard_name }} • {{ isSuperAdminRole(role.name) ? mockAllPermissions.length : (role.permissions?.length || 0) }} permissions
                      </p>
                      <div v-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1 mt-2">
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
                    v-for="permission in mockAllPermissions"
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
                    v-for="permission in mockAllPermissions.filter(p => hasPermission(p.id))"
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
        </div>

        <!-- Features List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold mb-2">Key Features:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Interactive role checkboxes with active state</li>
              <li>Super-admin role special handling</li>
              <li>Permission inheritance visualization</li>
              <li>Edit mode with save/cancel functionality</li>
              <li>Real-time role and permission counting</li>
              <li>Visual indicators for active roles</li>
              <li>Permission preview in role cards</li>
              <li>Responsive design for all screen sizes</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Super Admin Benefits:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Automatically inherits ALL permissions</li>
              <li>Special visual indicators and badges</li>
              <li>Clear notification about privileges</li>
              <li>Simplified permission view</li>
              <li>Cannot lose permissions through role changes</li>
              <li>System-wide access guaranteed</li>
            </ul>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
