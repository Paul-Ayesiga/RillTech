<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';
import AdminSidebarHeader from '@/components/AdminSidebarHeader.vue';
import { useScrollPreservation } from '@/composables/useScrollPreservation';
import type { BreadcrumbItem } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Initialize scroll preservation
useScrollPreservation();
</script>

<template>
    <AppShell variant="sidebar">
        <AdminSidebar />
        <AppContent variant="sidebar">
            <AdminSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
