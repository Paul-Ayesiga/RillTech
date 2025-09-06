<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, DollarSign, Users, BarChart, Webhook } from 'lucide-vue-next';

const stripeManagementItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/stripe/dashboard',
        icon: BarChart,
    },
    {
        title: 'Products & Prices',
        href: '/admin/stripe/products',
        icon: DollarSign,
    },
    {
        title: 'Subscriptions',
        href: '/admin/stripe/subscriptions',
        icon: CreditCard,
    },
    {
        title: 'Customers',
        href: '/admin/stripe/customers',
        icon: Users,
    },
    {
        title: 'Webhooks',
        href: '/admin/stripe/webhooks',
        icon: Webhook,
    },
];

const page = usePage<SharedData>();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Stripe Management</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in stripeManagementItems" :key="item.title">
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
