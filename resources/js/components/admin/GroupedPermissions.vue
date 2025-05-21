<script setup lang="ts">
import { type PermissionGroup, type Permission } from '@/types';
import DataTableGroupedPermissions from '@/components/admin/DataTableGroupedPermissions.vue';

const props = defineProps<{
    permissionGroups: PermissionGroup[];
    ungroupedPermissions: Permission[];
}>();

const emit = defineEmits<{
    (e: 'edit', permission: Permission): void;
    (e: 'delete', permission: Permission): void;
    (e: 'bulk-delete', ids: number[]): void;
    (e: 'bulk-assign-group', ids: number[], groupId: number | null): void;
    (e: 'bulk-assign-roles', ids: number[], roleIds: number[]): void;
    (e: 'open-bulk-assign-group-dialog', ids: number[]): void;
    (e: 'open-bulk-assign-roles-dialog', ids: number[]): void;
}>();
</script>

<template>
    <div class="space-y-8">
        <!-- Grouped Permissions -->
        <div v-for="group in props.permissionGroups" :key="group.id">
            <DataTableGroupedPermissions
                :permissions="group.permissions || []"
                :group-name="group.name"
                :group-color="group.color"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
                @bulk-delete="emit('bulk-delete', $event)"
                @bulk-assign-group="emit('bulk-assign-group', $event[0], $event[1])"
                @bulk-assign-roles="emit('bulk-assign-roles', $event[0], $event[1])"
                @open-bulk-assign-group-dialog="(ids) => { console.log('GroupedPermissions received IDs:', ids); emit('open-bulk-assign-group-dialog', ids); }"
                @open-bulk-assign-roles-dialog="(ids) => { console.log('GroupedPermissions received IDs:', ids); emit('open-bulk-assign-roles-dialog', ids); }"
            />
        </div>

        <!-- Ungrouped Permissions -->
        <DataTableGroupedPermissions
            :permissions="props.ungroupedPermissions"
            group-name="Ungrouped Permissions"
            @edit="emit('edit', $event)"
            @delete="emit('delete', $event)"
            @bulk-delete="emit('bulk-delete', $event)"
            @bulk-assign-group="emit('bulk-assign-group', $event[0], $event[1])"
            @bulk-assign-roles="emit('bulk-assign-roles', $event[0], $event[1])"
            @open-bulk-assign-group-dialog="(ids) => { console.log('GroupedPermissions received IDs:', ids); emit('open-bulk-assign-group-dialog', ids); }"
            @open-bulk-assign-roles-dialog="(ids) => { console.log('GroupedPermissions received IDs:', ids); emit('open-bulk-assign-roles-dialog', ids); }"
        />
    </div>
</template>
