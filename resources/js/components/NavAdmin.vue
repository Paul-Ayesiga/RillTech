<script setup lang="ts">
import AdminInfo from '@/components/AdminInfo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { type SharedData, type User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, Sun, Moon, Laptop, } from 'lucide-vue-next';
import AdminMenuContent from './AdminMenuContent.vue';
import { useColorMode } from '@vueuse/core';
import { ref, watch } from 'vue';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';


const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const { isMobile, state } = useSidebar();


const colorMode = useColorMode();

// Create a ref to track the selected theme
const selectedTheme = ref(colorMode.value);

// Watch for changes to selectedTheme and update colorMode
watch(selectedTheme, (newTheme) => {
    if (newTheme === 'light' || newTheme === 'dark' || newTheme === 'auto') {
        colorMode.value = newTheme;
    }
});

// Watch for external changes to colorMode
watch(colorMode, (newMode) => {
    selectedTheme.value = newMode;
});
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <AdminInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'" align="end" :side-offset="4">
                    <div class="p-2">
                        <ToggleGroup type="single" variant="outline" size="sm" class="w-full grid grid-cols-3 gap-1"
                            v-model="selectedTheme">
                            <ToggleGroupItem value="light" class="flex items-center justify-center gap-1">
                                <Sun class="h-3.5 w-3.5 text-yellow-200" />
                                <!-- <span class="text-xs">Light</span> -->
                            </ToggleGroupItem>
                            <ToggleGroupItem value="dark" class="flex items-center justify-center gap-1">
                                <Moon class="h-3.5 w-3.5 text-indigo-500" />
                                <!-- <span class="text-xs">Dark</span> -->
                            </ToggleGroupItem>
                            <ToggleGroupItem value="auto" class="flex items-center justify-center gap-1">
                                <Laptop class="h-3.5 w-3.5 text-gray-400 " />
                                <!-- <span class="text-xs">System</span> -->
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>
                    <DropdownMenuSeparator />
                    <AdminMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
