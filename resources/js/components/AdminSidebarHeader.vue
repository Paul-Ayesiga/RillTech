<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import AdminNotifications from '@/components/admin/AdminNotifications.vue';
import type { BreadcrumbItem } from '@/types';
import { Search, Command } from 'lucide-vue-next';
import { ref } from 'vue';

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>(), {
    breadcrumbs: () => []
});

// Function to trigger command palette
const triggerCommandPalette = () => {
    // Dispatch a custom event that the command palette can listen to
    window.dispatchEvent(new CustomEvent('open-command-palette'));
};
</script>

<template>
    <header
        class="sticky top-0 z-10 backdrop-blur-xl flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">

        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <Button
                variant="ghost"
                size="sm"
                @click="triggerCommandPalette"
                class="flex items-center gap-2 text-muted-foreground hover:text-foreground"
            >
                <Search class="h-4 w-4" />
                <span class="hidden sm:inline">Search</span>
                <kbd class="pointer-events-none hidden h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium opacity-100 sm:flex">
                    <span class="text-xs">⌘</span>K
                </kbd>
            </Button>
            <AdminNotifications />
        </div>
    </header>
</template>
