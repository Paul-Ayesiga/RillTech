<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import DataTableUsers from '@/components/admin/DataTableUsers.vue';

// Icons
import { AlertCircle, Plus, User as UserIcon } from 'lucide-vue-next';

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object,
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard',
  },
  {
    title: 'User Management',
    href: '/admin/users',
  },
];

// Delete user dialog
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);
const isDeleting = ref(false);

// Handle user actions
const handleEditUser = (user: User) => {
  router.visit(route('admin.users.edit', user.id));
};

const handleViewUser = (user: User) => {
  router.visit(route('admin.users.show', user.id));
};

const handleDeleteUser = (user: User) => {
  userToDelete.value = user;
  isDeleteDialogOpen.value = true;
};

// Delete user
const deleteUser = () => {
  if (!userToDelete.value) return;

  isDeleting.value = true;

  router.delete(route('admin.users.destroy', userToDelete.value.id), {
    onSuccess: () => {
      toast.success('User deleted successfully');
      isDeleteDialogOpen.value = false;
      userToDelete.value = null;
      isDeleting.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to delete user: ' + Object.values(errors).flat().join(', '));
      isDeleting.value = false;
    },
  });
};
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <Head title="User Management" />

    <div class="container py-6 px-3">
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold">User Management</h1>
        <Button as-child>
          <Link :href="route('admin.users.create')" class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Create User
          </Link>
        </Button>
      </div>

      <!-- Users data table -->
      <Card>
        <CardContent>
          <DataTableUsers
            :users="users.data"
            @edit="handleEditUser"
            @delete="handleDeleteUser"
            @view="handleViewUser"
          />
        </CardContent>
      </Card>
    </div>

    <!-- Delete User Dialog -->
    <Dialog v-model:open="isDeleteDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Delete User</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this user? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div v-if="userToDelete" class="py-4">
          <div class="rounded-md border p-4">
            <div class="flex items-center gap-3">
              <UserIcon class="h-10 w-10 text-muted-foreground" />
              <div>
                <p class="font-medium">{{ userToDelete.name }}</p>
                <p class="text-sm text-muted-foreground">{{ userToDelete.email }}</p>
              </div>
            </div>
          </div>
          <div class="mt-4 rounded-md border border-destructive/20 bg-destructive/10 p-4 text-destructive">
            <div class="flex items-center gap-2">
              <AlertCircle class="h-5 w-5" />
              <p>This will permanently delete the user and all associated data.</p>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isDeleteDialogOpen = false" :disabled="isDeleting">
            Cancel
          </Button>
          <Button variant="destructive" @click="deleteUser" :disabled="isDeleting">
            <span v-if="isDeleting" class="flex items-center gap-1">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Deleting...
            </span>
            <span v-else>Delete User</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
