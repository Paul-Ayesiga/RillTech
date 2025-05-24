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
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
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

// Bulk delete dialog
const isBulkDeleteDialogOpen = ref(false);
const usersToDelete = ref<number[]>([]);
const isBulkDeleting = ref(false);

// Bulk assign roles dialog
const isBulkAssignRolesDialogOpen = ref(false);
const usersToAssignRoles = ref<number[]>([]);
const selectedRoleIds = ref<number[]>([]);
const isBulkAssigningRoles = ref(false);

// Bulk email dialog
const isBulkEmailDialogOpen = ref(false);
const usersToEmail = ref<number[]>([]);
const emailSubject = ref('');
const emailMessage = ref('');
const isSendingBulkEmail = ref(false);

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

// Bulk action handlers
const handleBulkDelete = (userIds: number[]) => {
  usersToDelete.value = userIds;
  isBulkDeleteDialogOpen.value = true;
};

const handleBulkAssignRoles = (userIds: number[]) => {
  usersToAssignRoles.value = userIds;
  selectedRoleIds.value = [];
  isBulkAssignRolesDialogOpen.value = true;
};

const handleBulkEmail = (userIds: number[]) => {
  usersToEmail.value = userIds;
  emailSubject.value = '';
  emailMessage.value = '';
  isBulkEmailDialogOpen.value = true;
};

// Execute bulk delete
const executeBulkDelete = () => {
  if (usersToDelete.value.length === 0) return;

  isBulkDeleting.value = true;

  router.post(route('admin.users.bulk-delete'), {
    user_ids: usersToDelete.value
  }, {
    onSuccess: () => {
      toast.success(`${usersToDelete.value.length} users deleted successfully`);
      isBulkDeleteDialogOpen.value = false;
      usersToDelete.value = [];
      isBulkDeleting.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to delete users: ' + Object.values(errors).flat().join(', '));
      isBulkDeleting.value = false;
    },
  });
};

// Execute bulk role assignment
const executeBulkAssignRoles = () => {
  if (usersToAssignRoles.value.length === 0 || selectedRoleIds.value.length === 0) return;

  isBulkAssigningRoles.value = true;

  router.post(route('admin.users.bulk-assign-roles'), {
    user_ids: usersToAssignRoles.value,
    role_ids: selectedRoleIds.value
  }, {
    onSuccess: () => {
      toast.success(`Roles assigned to ${usersToAssignRoles.value.length} users successfully`);
      isBulkAssignRolesDialogOpen.value = false;
      usersToAssignRoles.value = [];
      selectedRoleIds.value = [];
      isBulkAssigningRoles.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to assign roles: ' + Object.values(errors).flat().join(', '));
      isBulkAssigningRoles.value = false;
    },
  });
};

// Execute bulk email
const executeBulkEmail = () => {
  if (usersToEmail.value.length === 0 || !emailSubject.value || !emailMessage.value) return;

  isSendingBulkEmail.value = true;

  router.post(route('admin.users.bulk-email'), {
    user_ids: usersToEmail.value,
    subject: emailSubject.value,
    message: emailMessage.value
  }, {
    onSuccess: () => {
      toast.success(`Email sent to ${usersToEmail.value.length} users successfully`);
      isBulkEmailDialogOpen.value = false;
      usersToEmail.value = [];
      emailSubject.value = '';
      emailMessage.value = '';
      isSendingBulkEmail.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to send emails: ' + Object.values(errors).flat().join(', '));
      isSendingBulkEmail.value = false;
    },
  });
};

// Toggle role selection
const toggleRole = (roleId: number) => {
  const index = selectedRoleIds.value.indexOf(roleId);
  if (index === -1) {
    selectedRoleIds.value.push(roleId);
  } else {
    selectedRoleIds.value.splice(index, 1);
  }
};

// Export handlers
const handleExportExcel = (userIds: number[]) => {
  const idsString = userIds.join(',');
  window.location.href = `/admin/users/export-excel?ids=${idsString}`;
  toast.success(`Exporting ${userIds.length} users to Excel`);
};

const handleExportPdf = (userIds: number[]) => {
  const idsString = userIds.join(',');
  window.location.href = `/admin/users/export-pdf?ids=${idsString}`;
  toast.success(`Exporting ${userIds.length} users to PDF`);
};

// Account status handlers
const handleSuspendUser = (user: User) => {
  router.patch(route('admin.users.suspend', user.id), {}, {
    onSuccess: () => {
      toast.success(`User ${user.name} has been suspended`);
    },
    onError: (errors) => {
      toast.error('Failed to suspend user: ' + Object.values(errors).flat().join(', '));
    },
  });
};

const handleBanUser = (user: User) => {
  router.patch(route('admin.users.ban', user.id), {}, {
    onSuccess: () => {
      toast.success(`User ${user.name} has been banned`);
    },
    onError: (errors) => {
      toast.error('Failed to ban user: ' + Object.values(errors).flat().join(', '));
    },
  });
};

const handleActivateUser = (user: User) => {
  router.patch(route('admin.users.activate', user.id), {}, {
    onSuccess: () => {
      toast.success(`User ${user.name} has been activated`);
    },
    onError: (errors) => {
      toast.error('Failed to activate user: ' + Object.values(errors).flat().join(', '));
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
            @suspend="handleSuspendUser"
            @ban="handleBanUser"
            @activate="handleActivateUser"
            @bulk-delete="handleBulkDelete"
            @bulk-assign-roles="handleBulkAssignRoles"
            @bulk-email="handleBulkEmail"
            @open-bulk-assign-roles-dialog="handleBulkAssignRoles"
            @export-excel="handleExportExcel"
            @export-pdf="handleExportPdf"
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

    <!-- Bulk Delete Dialog -->
    <Dialog v-model:open="isBulkDeleteDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Delete Multiple Users</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete {{ usersToDelete.length }} users? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <div class="rounded-md border border-destructive/20 bg-destructive/10 p-4 text-destructive">
            <div class="flex items-center gap-2">
              <AlertCircle class="h-4 w-4" />
              <p class="text-sm font-medium">Warning: This will permanently delete {{ usersToDelete.length }} users and all their data.</p>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isBulkDeleteDialogOpen = false" :disabled="isBulkDeleting">
            Cancel
          </Button>
          <Button variant="destructive" @click="executeBulkDelete" :disabled="isBulkDeleting">
            {{ isBulkDeleting ? 'Deleting...' : `Delete ${usersToDelete.length} Users` }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Bulk Assign Roles Dialog -->
    <Dialog v-model:open="isBulkAssignRolesDialogOpen">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Assign Roles to Multiple Users</DialogTitle>
          <DialogDescription>
            Select roles to assign to {{ usersToAssignRoles.length }} users. New roles will be added to existing roles (existing roles will be kept).
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div v-for="role in roles" :key="role.id" class="flex items-start space-x-3 p-3 rounded-lg border">
                <input
                  :id="`bulk-role-${role.id}`"
                  type="checkbox"
                  :checked="selectedRoleIds.includes(role.id)"
                  @change="toggleRole(role.id)"
                  class="mt-1 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                />
                <div class="flex-1 space-y-1">
                  <label
                    :for="`bulk-role-${role.id}`"
                    class="text-sm font-medium leading-none cursor-pointer flex items-center gap-2"
                  >
                    {{ role.name }}
                    <Badge v-if="selectedRoleIds.includes(role.id)" variant="default" class="text-xs">Selected</Badge>
                  </label>
                  <p class="text-xs text-muted-foreground">
                    {{ role.guard_name }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isBulkAssignRolesDialogOpen = false" :disabled="isBulkAssigningRoles">
            Cancel
          </Button>
          <Button @click="executeBulkAssignRoles" :disabled="isBulkAssigningRoles || selectedRoleIds.length === 0">
            {{ isBulkAssigningRoles ? 'Assigning...' : `Assign to ${usersToAssignRoles.length} Users` }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Bulk Email Dialog -->
    <Dialog v-model:open="isBulkEmailDialogOpen">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Send Email to Multiple Users</DialogTitle>
          <DialogDescription>
            Compose an email to send to {{ usersToEmail.length }} users.
          </DialogDescription>
        </DialogHeader>
        <div class="py-4 space-y-4">
          <div class="space-y-2">
            <Label for="email-subject">Subject</Label>
            <input
              id="email-subject"
              v-model="emailSubject"
              type="text"
              placeholder="Enter email subject"
              class="w-full h-10 px-3 py-2 text-sm border border-input bg-background rounded-md ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>
          <div class="space-y-2">
            <Label for="email-message">Message</Label>
            <Textarea
              id="email-message"
              v-model="emailMessage"
              placeholder="Enter your message here..."
              rows="6"
              class="w-full"
            />
          </div>
          <div class="text-sm text-muted-foreground">
            This email will be sent to {{ usersToEmail.length }} users.
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isBulkEmailDialogOpen = false" :disabled="isSendingBulkEmail">
            Cancel
          </Button>
          <Button @click="executeBulkEmail" :disabled="isSendingBulkEmail || !emailSubject || !emailMessage">
            {{ isSendingBulkEmail ? 'Sending...' : `Send to ${usersToEmail.length} Users` }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
