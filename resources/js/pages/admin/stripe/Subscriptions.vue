<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { toast } from 'vue-sonner';
import { ref, watch } from 'vue';
import { AlertCircle, Search, CreditCard, User } from 'lucide-vue-next';

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

// Search and filters
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const perPage = ref(props.filters?.per_page || 10);

// Apply filters
const applyFilters = () => {
  router.get(
    route('admin.stripe.subscriptions'),
    {
      search: search.value,
      status: status.value,
      per_page: perPage.value,
      page: 1, // Reset to first page when filters change
    },
    {
      preserveState: true,
      replace: true,
    }
  );
};

// Watch for filter changes
watch([search, status, perPage], () => {
  applyFilters();
});

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Get status badge color
const getStatusColor = (status) => {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    case 'trialing':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
    case 'past_due':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    case 'canceled':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    case 'incomplete':
      return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
    case 'incomplete_expired':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
  }
};
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

      <!-- Filters -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
          <div class="relative">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="search"
              placeholder="Search by customer name or email..."
              class="pl-8"
            />
          </div>
        </div>
        <Select v-model="status" class="w-full sm:w-[150px]">
          <SelectTrigger>
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="trialing">Trialing</SelectItem>
            <SelectItem value="past_due">Past Due</SelectItem>
            <SelectItem value="canceled">Canceled</SelectItem>
            <SelectItem value="incomplete">Incomplete</SelectItem>
            <SelectItem value="incomplete_expired">Incomplete Expired</SelectItem>
          </SelectContent>
        </Select>
        <Select v-model="perPage" class="w-full sm:w-[100px]">
          <SelectTrigger>
            <SelectValue placeholder="Per page" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="5">5</SelectItem>
            <SelectItem :value="10">10</SelectItem>
            <SelectItem :value="25">25</SelectItem>
            <SelectItem :value="50">50</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Subscriptions table -->
      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Period End</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="subscription in subscriptions" :key="subscription.id">
                <TableCell>
                  <div class="font-mono text-xs">{{ subscription.stripe_id }}</div>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <User class="h-4 w-4 text-muted-foreground" />
                    <div>
                      <p class="font-medium">{{ subscription.user.name }}</p>
                      <p class="text-sm text-muted-foreground">{{ subscription.user.email }}</p>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <span :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    getStatusColor(subscription.status)
                  ]">
                    {{ subscription.status }}
                  </span>
                </TableCell>
                <TableCell>{{ formatDate(subscription.current_period_end) }}</TableCell>
                <TableCell>{{ formatDate(subscription.created_at) }}</TableCell>
                <TableCell class="text-right">
                  <Button as-child variant="ghost" size="sm">
                    <Link :href="route('admin.stripe.subscriptions.show', subscription.id)">
                      View
                    </Link>
                  </Button>
                </TableCell>
              </TableRow>
              <TableRow v-if="subscriptions && subscriptions.length === 0">
                <TableCell colspan="6" class="h-24 text-center">
                  No subscriptions found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
        <CardFooter class="flex items-center justify-between border-t p-4">
          <div class="text-sm text-muted-foreground">
            Showing {{ subscriptions ? subscriptions.length : 0 }} of {{ pagination ? pagination.total : 0 }} results
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="!pagination || pagination.current_page <= 1"
              @click="router.get(route('admin.stripe.subscriptions'), { page: pagination.current_page - 1, ...filters }, { preserveState: true })"
            >
              Previous
            </Button>
            <span class="text-sm text-muted-foreground">
              Page {{ pagination ? pagination.current_page : 1 }} of {{ pagination ? pagination.last_page : 1 }}
            </span>
            <Button
              variant="outline"
              size="sm"
              :disabled="!pagination || pagination.current_page >= pagination.last_page"
              @click="router.get(route('admin.stripe.subscriptions'), { page: pagination.current_page + 1, ...filters }, { preserveState: true })"
            >
              Next
            </Button>
          </div>
        </CardFooter>
      </Card>
    </div>
  </AdminLayout>
</template>
