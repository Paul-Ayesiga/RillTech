<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import DataTableNewsletterSubscriptions from '@/components/admin/DataTableNewsletterSubscriptions.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'vue-sonner';
import { type BreadcrumbItem } from '@/types';
import {
  Mail,
  Users,
  UserCheck,
  Calendar,
  TrendingUp
} from 'lucide-vue-next';

interface NewsletterSubscription {
  id: number;
  email: string;
  name: string | null;
  status: 'active' | 'unsubscribed';
  source: string | null;
  subscribed_at: string;
  unsubscribed_at: string | null;
  ip_address: string | null;
}

interface Stats {
  total: number;
  active: number;
  unsubscribed: number;
  today: number;
  this_week: number;
  this_month: number;
}

interface Props {
  subscriptions: {
    data: NewsletterSubscription[];
    links: any[];
    meta: any;
  };
  stats: Stats;
}

const props = defineProps<Props>();

// Methods
const handleDeleteSubscription = async (subscription: NewsletterSubscription) => {
  try {
    await router.delete(`/admin/newsletter-subscriptions/${subscription.id}`);
    toast('Subscription deleted successfully');
  } catch (error) {
    toast('Error deleting subscription');
  }
};

const handleBulkDelete = async (subscriptions: NewsletterSubscription[]) => {
  if (subscriptions.length === 0) return;

  try {
    await router.post('/admin/newsletter-subscriptions/bulk-delete', {
      subscription_ids: subscriptions.map(s => s.id)
    });
    toast('Subscriptions deleted successfully');
  } catch (error) {
    toast('Error deleting subscriptions');
  }
};

const handleBulkEmail = async (subscriptions: NewsletterSubscription[]) => {
  // This is handled by the drawer component directly
  // The drawer will emit 'sent' event when emails are queued
};

const handleExport = () => {
  window.open('/admin/newsletter-subscriptions/export-csv', '_blank');
};
</script>

<template>
  <Head title="Newsletter Subscriptions" />

  <AdminLayout :breadcrumbs="[
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Newsletter Subscriptions', href: '#' }
  ]">
    <div class="space-y-6 px-3 py-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">Newsletter Subscriptions</h1>
          <p class="text-muted-foreground">Manage your newsletter subscribers and view analytics</p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Subscribers</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">All time subscribers</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Subscribers</CardTitle>
            <UserCheck class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.active.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">Currently subscribed</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">This Month</CardTitle>
            <TrendingUp class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ stats.this_month.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">New this month</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Today</CardTitle>
            <Calendar class="h-4 w-4 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-purple-600">{{ stats.today.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">New today</p>
          </CardContent>
        </Card>
      </div>

      <!-- DataTable -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Mail class="h-5 w-5" />
            Newsletter Subscribers
          </CardTitle>
        </CardHeader>
        <CardContent>
          <DataTableNewsletterSubscriptions
            :subscriptions="subscriptions.data"
            @delete="handleDeleteSubscription"
            @bulk-delete="handleBulkDelete"
            @bulk-email="handleBulkEmail"
            @export="handleExport"
          />
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
