<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type PermissionGroup } from '@/types';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { PlusCircle, Pencil, Trash2, AlertTriangle, Info, LoaderCircle, Palette } from 'lucide-vue-next';

// Define breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Permission Groups',
        href: '/admin/permission-groups',
    },
];

// Define props interface
interface Props {
    groups: PermissionGroup[];
}

const props = defineProps<Props>();

// Group dialog state
const isGroupDialogOpen = ref(false);
const isEditingGroup = ref(false);
const currentGroupId = ref<number | null>(null);
const groupToDelete = ref<PermissionGroup | null>(null);
const isDeleteGroupDialogOpen = ref(false);
const isDeletingGroup = ref(false);

// Group form
const groupForm = useForm({
    name: '',
    description: '',
    color: '#6366f1', // Default color (indigo)
    display_order: 0,
});

// Function to reset group form
const resetGroupForm = () => {
    groupForm.name = '';
    groupForm.description = '';
    groupForm.color = '#6366f1';
    groupForm.display_order = 0;
    groupForm.clearErrors();
};

// Open group dialog for creating a new group
const openCreateGroupDialog = () => {
    isEditingGroup.value = false;
    currentGroupId.value = null;
    resetGroupForm();
    isGroupDialogOpen.value = true;
};

// Open group dialog for editing an existing group
const openEditGroupDialog = (group: PermissionGroup) => {
    isEditingGroup.value = true;
    currentGroupId.value = group.id;

    // Reset form first
    resetGroupForm();

    // Set form values
    groupForm.name = group.name;
    groupForm.description = group.description || '';
    groupForm.color = group.color || '#6366f1';
    groupForm.display_order = group.display_order;

    isGroupDialogOpen.value = true;
};

// Handle group dialog close
const handleGroupDialogClose = () => {
    resetGroupForm();
    isGroupDialogOpen.value = false;
};

// Submit group form
const submitGroupForm = () => {
    if (isEditingGroup.value && currentGroupId.value) {
        groupForm.put(`/admin/permission-groups/${currentGroupId.value}`, {
            onSuccess: () => {
                handleGroupDialogClose();
                toast.success('Permission group updated successfully');
            },
            onError: () => {
                toast.error('Failed to update permission group');
            }
        });
    } else {
        groupForm.post('/admin/permission-groups', {
            onSuccess: () => {
                handleGroupDialogClose();
                toast.success('Permission group created successfully');
            },
            onError: () => {
                toast.error('Failed to create permission group');
            }
        });
    }
};

// Open delete group dialog
const openDeleteGroupDialog = (group: PermissionGroup) => {
    groupToDelete.value = group;
    isDeleteGroupDialogOpen.value = true;
};

// Delete group
const deleteGroup = () => {
    if (!groupToDelete.value) return;

    isDeletingGroup.value = true;

    useForm({}).delete(`/admin/permission-groups/${groupToDelete.value.id}`, {
        onSuccess: () => {
            toast.success('Permission group deleted successfully');
            isDeleteGroupDialogOpen.value = false;
            groupToDelete.value = null;
            isDeletingGroup.value = false;
        },
        onError: (error) => {
            toast.error(error.message || 'Failed to delete permission group');
            isDeletingGroup.value = false;
        }
    });
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Permission Groups Management" />

        <Toaster />

        <div class="container max-w-7xl mx-auto px-4 py-8">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-2">
                    <Palette class="h-6 w-6 text-primary" />
                    <h1 class="text-3xl font-bold">Permission Groups Management</h1>
                </div>
                <p class="text-muted-foreground">Organize permissions into logical groups for easier management</p>
            </div>

            <Alert className="mb-6">
                <Info className="h-4 w-4" />
                <AlertTitle>Permission Groups</AlertTitle>
                <AlertDescription>
                    Permission groups help organize permissions into logical categories. Each permission can be assigned to a group, making it easier to manage and understand permissions in your application.
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader class="pb-4">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <CardTitle class="text-2xl">Permission Groups</CardTitle>
                            <CardDescription>Manage permission groups in your application</CardDescription>
                        </div>
                        <Button @click="openCreateGroupDialog" size="sm" class="w-full sm:w-auto">
                            <PlusCircle class="mr-2 h-4 w-4" />
                            Add Group
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[200px]">Name</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="w-[100px]">Color</TableHead>
                                    <TableHead class="w-[100px]">Order</TableHead>
                                    <TableHead class="w-[100px]">Permissions</TableHead>
                                    <TableHead class="w-[100px] text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="group in props.groups" :key="group.id">
                                    <TableCell class="font-medium">{{ group.name }}</TableCell>
                                    <TableCell>{{ group.description || 'No description' }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-4 h-4 rounded-full"
                                                :style="{ backgroundColor: group.color || '#6366f1' }"
                                            ></div>
                                            <span>{{ group.color || 'Default' }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>{{ group.display_order }}</TableCell>
                                    <TableCell>
                                        <Badge>{{ group.permissions_count || 0 }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" @click="openEditGroupDialog(group)" title="Edit Group">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openDeleteGroupDialog(group)"
                                                :disabled="group.permissions_count && group.permissions_count > 0"
                                                title="Delete Group"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="props.groups.length === 0">
                                    <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                        No permission groups found. Create your first group to get started.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Group Dialog -->
        <Dialog
            v-model:open="isGroupDialogOpen"
            @update:open="(open) => !open && handleGroupDialogClose()"
        >
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="text-xl">{{ isEditingGroup ? 'Edit Permission Group' : 'Create Permission Group' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditingGroup ? 'Update permission group details' : 'Add a new permission group to the system' }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitGroupForm">
                    <div class="grid gap-6 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="name" class="text-right font-medium">Name</Label>
                            <div class="col-span-3 space-y-1">
                                <Input
                                    id="name"
                                    v-model="groupForm.name"
                                    placeholder="Enter group name"
                                    class="w-full"
                                />
                                <p v-if="groupForm.errors?.name" class="text-sm text-destructive">{{ groupForm.errors.name }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-start gap-4">
                            <Label for="description" class="text-right font-medium pt-2">Description</Label>
                            <div class="col-span-3 space-y-1">
                                <Textarea
                                    id="description"
                                    v-model="groupForm.description"
                                    placeholder="Enter group description"
                                    class="w-full"
                                    rows="3"
                                />
                                <p v-if="groupForm.errors?.description" class="text-sm text-destructive">{{ groupForm.errors.description }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="color" class="text-right font-medium">Color</Label>
                            <div class="col-span-3 space-y-1">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        id="color"
                                        v-model="groupForm.color"
                                        class="w-10 h-10 rounded cursor-pointer"
                                    />
                                    <Input
                                        v-model="groupForm.color"
                                        placeholder="#6366f1"
                                        class="w-full"
                                    />
                                </div>
                                <p v-if="groupForm.errors?.color" class="text-sm text-destructive">{{ groupForm.errors.color }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="display_order" class="text-right font-medium">Display Order</Label>
                            <div class="col-span-3 space-y-1">
                                <Input
                                    id="display_order"
                                    type="number"
                                    v-model="groupForm.display_order"
                                    placeholder="0"
                                    class="w-full"
                                    min="0"
                                />
                                <p v-if="groupForm.errors?.display_order" class="text-sm text-destructive">{{ groupForm.errors.display_order }}</p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="handleGroupDialogClose"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="groupForm.processing"
                        >
                            <LoaderCircle v-if="groupForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isEditingGroup ? 'Update Group' : 'Create Group' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Group Confirmation Dialog -->
        <Dialog
            v-model:open="isDeleteGroupDialogOpen"
            @update:open="(open) => !open && (groupToDelete = null)"
        >
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="text-xl flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-destructive" />
                        Confirm Delete Group
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the permission group "{{ groupToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Alert variant="destructive" class="mb-4">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertTitle>Warning</AlertTitle>
                        <AlertDescription>
                            You can only delete groups that have no permissions assigned to them.
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteGroupDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deleteGroup"
                        :disabled="isDeletingGroup"
                    >
                        <LoaderCircle v-if="isDeletingGroup" class="mr-2 h-4 w-4 animate-spin" />
                        Delete Group
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
