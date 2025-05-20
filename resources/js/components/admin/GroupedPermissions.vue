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
            />
        </div>

        <!-- Ungrouped Permissions -->
        <DataTableGroupedPermissions
            :permissions="props.ungroupedPermissions"
            group-name="Ungrouped Permissions"
            @edit="emit('edit', $event)"
            @delete="emit('delete', $event)"
        />
    </div>
</template>
