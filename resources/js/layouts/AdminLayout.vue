<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminSidebarLayout.vue';
import AdminCommandPalette from '@/components/admin/AdminCommandPalette.vue';
import type { BreadcrumbItem } from '@/types';
import { Toaster } from '@/components/ui/sonner'

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <Toaster />
    <AdminCommandPalette />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AdminLayout>
</template>
