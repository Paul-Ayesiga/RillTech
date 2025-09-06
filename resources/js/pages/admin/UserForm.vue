<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type User, type Role } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Toaster } from 'vue-sonner';

// Icons
import { ArrowLeft, User as UserIcon, Mail, Key, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
  user: Object as () => User | undefined,
  roles: Array as () => Role[],
  userRoles: Array as () => number[],
});

const isEditMode = computed(() => !!props.user);

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
    title: isEditMode.value ? 'Edit User' : 'Create User',
    href: isEditMode.value ? `/admin/users/${props.user?.id}/edit` : '/admin/users/create',
  },
];

// Form
const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: '',
  password_confirmation: '',
  roles: props.userRoles || [],
});

// Selected roles
const selectedRoles = ref<number[]>(props.userRoles || []);

// Toggle role selection
const toggleRole = (roleId: number) => {
  const index = selectedRoles.value.indexOf(roleId);
  if (index === -1) {
    selectedRoles.value.push(roleId);
  } else {
    selectedRoles.value.splice(index, 1);
  }
  form.roles = selectedRoles.value;
};

// Check if role is selected
const isRoleSelected = (roleId: number) => {
  return selectedRoles.value.includes(roleId);
};

// Check if role is super-admin
const isSuperAdmin = (roleName: string) => {
  return roleName === 'super-admin';
};

// Submit form
const submit = () => {
  if (isEditMode.value) {
    form.put(route('admin.users.update', props.user?.id), {
      onSuccess: () => {
        toast.success('User updated successfully');
      },
      onError: (errors) => {
        toast.error('Failed to update user');
        console.error(errors);
      },
    });
  } else {
    form.post(route('admin.users.store'), {
      onSuccess: () => {
        toast.success('User created successfully');
        form.reset();
        selectedRoles.value = [];
      },
      onError: (errors) => {
        toast.error('Failed to create user');
        console.error(errors);
      },
    });
  }
};
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <Head :title="isEditMode ? 'Edit User' : 'Create User'" />
    
    <Toaster />
    
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center gap-4">
        <Button as-child variant="outline" size="icon">
          <Link :href="route('admin.users.index')">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <h1 class="text-2xl font-bold">{{ isEditMode ? 'Edit User' : 'Create User' }}</h1>
      </div>
      
      <form @submit.prevent="submit">
        <div class="grid gap-6 md:grid-cols-2">
          <!-- User Information -->
          <Card>
            <CardHeader>
              <CardTitle>User Information</CardTitle>
              <CardDescription>
                {{ isEditMode ? 'Update user details' : 'Enter details for the new user' }}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid gap-4">
                <!-- Name -->
                <div class="grid gap-2">
                  <Label for="name" class="flex items-center gap-1">
                    <UserIcon class="h-4 w-4" />
                    Name
                  </Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    :class="{ 'border-red-500': form.errors.name }"
                    required
                  />
                  <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                </div>
                
                <!-- Email -->
                <div class="grid gap-2">
                  <Label for="email" class="flex items-center gap-1">
                    <Mail class="h-4 w-4" />
                    Email
                  </Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    :class="{ 'border-red-500': form.errors.email }"
                    required
                  />
                  <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                </div>
                
                <!-- Password -->
                <div class="grid gap-2">
                  <Label for="password" class="flex items-center gap-1">
                    <Key class="h-4 w-4" />
                    Password {{ isEditMode ? '(leave blank to keep current)' : '' }}
                  </Label>
                  <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    :class="{ 'border-red-500': form.errors.password }"
                    :required="!isEditMode"
                  />
                  <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                </div>
                
                <!-- Password Confirmation -->
                <div class="grid gap-2">
                  <Label for="password_confirmation" class="flex items-center gap-1">
                    <Key class="h-4 w-4" />
                    Confirm Password
                  </Label>
                  <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    :required="!isEditMode || form.password"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
          
          <!-- Roles -->
          <Card>
            <CardHeader>
              <CardTitle>User Roles</CardTitle>
              <CardDescription>
                Assign roles to the user
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid gap-4">
                <p v-if="form.errors.roles" class="text-sm text-red-500">{{ form.errors.roles }}</p>
                
                <div v-for="role in roles" :key="role.id" class="flex items-start space-x-2">
                  <Checkbox
                    :id="`role-${role.id}`"
                    :checked="isRoleSelected(role.id)"
                    @update:checked="toggleRole(role.id)"
                  />
                  <div class="grid gap-1.5 leading-none">
                    <label
                      :for="`role-${role.id}`"
                      class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 flex items-center gap-1"
                    >
                      <ShieldCheck v-if="isSuperAdmin(role.name)" class="h-4 w-4 text-primary" />
                      {{ role.name }}
                    </label>
                    <p class="text-sm text-muted-foreground">
                      {{ role.guard_name }}
                    </p>
                  </div>
                </div>
                
                <div v-if="selectedRoles.length === 0" class="text-sm text-amber-600 dark:text-amber-400 mt-2">
                  Please select at least one role for the user.
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
        
        <!-- Form Actions -->
        <div class="mt-6 flex justify-end gap-4">
          <Button as-child variant="outline">
            <Link :href="route('admin.users.index')">
              Cancel
            </Link>
          </Button>
          <Button type="submit" :disabled="form.processing || selectedRoles.length === 0">
            <span v-if="form.processing" class="flex items-center gap-1">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isEditMode ? 'Updating...' : 'Creating...' }}
            </span>
            <span v-else>{{ isEditMode ? 'Update User' : 'Create User' }}</span>
          </Button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
