<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MessageSquare, Users, Send } from 'lucide-vue-next';

const communicationManagementItems: NavItem[] = [
    {
        title: 'Newsletter Subscriptions',
        href: '/admin/newsletter-subscriptions',
        icon: Mail,
    },
    {
        title: 'Contact Submissions',
        href: '/admin/contact-submissions',
        icon: MessageSquare,
    },
];

const page = usePage<SharedData>();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Communication</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in communicationManagementItems" :key="item.title">
                <SidebarMenuButton
                    as-child :is-active="page.url.startsWith(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
