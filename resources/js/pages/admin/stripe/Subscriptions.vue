<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';
import DataTableStripeSubscriptions from '@/components/admin/DataTableStripeSubscriptions.vue';

const props = defineProps({
  subscriptions: Array,
  pagination: Object,
  filters: Object,
  error: String,
  warning: String,
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard',
  },
  {
    title: 'Stripe Management',
    href: '/admin/stripe/dashboard',
  },
  {
    title: 'Subscriptions',
    href: '/admin/stripe/subscriptions',
  },
];

// Breadcrumbs for navigation
</script>

<template>
  <Head title="Stripe Subscriptions" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Stripe Subscriptions</h1>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Warning message -->
      <div v-if="warning" class="mb-6 rounded-lg border border-yellow-500/50 bg-yellow-500/10 p-4 text-yellow-700 dark:text-yellow-400">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ warning }}</p>
        </div>
      </div>

      <!-- Subscriptions DataTable -->
      <Card>
        <CardHeader>
          <CardTitle>Stripe Subscriptions</CardTitle>
          <CardDescription>
            Manage and view all Stripe subscriptions with advanced filtering and sorting.
          </CardDescription>
        </CardHeader>
        <CardContent class="px-3">
          <DataTableStripeSubscriptions :subscriptions="subscriptions || []" />
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
