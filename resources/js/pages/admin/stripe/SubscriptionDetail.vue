<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { AlertCircle, CreditCard, User, Calendar, Clock, CheckCircle, XCircle, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
  subscription: Object,
  error: String,
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
  {
    title: props.subscription ? `Subscription ${props.subscription.stripe_id}` : 'Subscription Detail',
    href: props.subscription ? `/admin/stripe/subscriptions/${props.subscription.id}` : '/admin/stripe/subscriptions',
  },
];

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Format currency
const formatCurrency = (amount, currency) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(amount);
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

// Cancel subscription
const cancelSubscription = (atPeriodEnd = true) => {
  // If subscription doesn't exist in database, show a warning
  if (!props.subscription.id) {
    toast.error('This subscription exists only in Stripe and not in your database. Please sync your database with Stripe first.');
    return;
  }

  router.post(route('admin.stripe.subscriptions.cancel', props.subscription.id), {
    at_period_end: atPeriodEnd,
  }, {
    onSuccess: () => {
      toast.success(`Subscription ${atPeriodEnd ? 'will be canceled at the end of the billing period' : 'has been canceled immediately'}`);
    },
    onError: (errors) => {
      toast.error('Failed to cancel subscription');
      console.error(errors);
    },
  });
};

// Resume subscription
const resumeSubscription = () => {
  // If subscription doesn't exist in database, show a warning
  if (!props.subscription.id) {
    toast.error('This subscription exists only in Stripe and not in your database. Please sync your database with Stripe first.');
    return;
  }

  router.post(route('admin.stripe.subscriptions.resume', props.subscription.id), {}, {
    onSuccess: () => {
      toast.success('Subscription has been resumed');
    },
    onError: (errors) => {
      toast.error('Failed to resume subscription');
      console.error(errors);
    },
  });
};
</script>

<template>
  <Head title="Subscription Detail" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Subscription Detail</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('admin.stripe.subscriptions')">
              Back to Subscriptions
            </Link>
          </Button>

          <!-- Subscription actions -->
          <div v-if="subscription">
            <!-- Resume subscription -->
            <Button
              v-if="subscription.status === 'canceled' && subscription.ends_at && !subscription.cancel_at_period_end"
              variant="default"
              @click="resumeSubscription"
              class="ml-2"
              :disabled="!subscription.id"
              :title="!subscription.id ? 'This subscription exists only in Stripe and not in your database' : ''"
            >
              Resume Subscription
            </Button>

            <!-- Cancel subscription -->
            <Dialog v-if="subscription.status === 'active' || subscription.status === 'trialing'">
              <DialogTrigger asChild>
                <Button
                  variant="destructive"
                  :disabled="!subscription.id"
                  :title="!subscription.id ? 'This subscription exists only in Stripe and not in your database' : ''"
                >
                  Cancel Subscription
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Cancel Subscription</DialogTitle>
                  <DialogDescription>
                    Are you sure you want to cancel this subscription? This action cannot be undone.
                  </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                  <p class="mb-4">Choose how you want to cancel this subscription:</p>
                  <div class="space-y-4">
                    <div class="flex items-start space-x-4 rounded-md border p-4">
                      <CheckCircle class="mt-0.5 h-5 w-5 text-green-500" />
                      <div>
                        <h4 class="font-medium">Cancel at Period End</h4>
                        <p class="text-sm text-muted-foreground">
                          The subscription will remain active until the end of the current billing period ({{ formatDate(subscription.current_period_end) }}), then it will be canceled.
                        </p>
                        <Button variant="outline" class="mt-2" @click="cancelSubscription(true)">
                          Cancel at Period End
                        </Button>
                      </div>
                    </div>
                    <div class="flex items-start space-x-4 rounded-md border p-4">
                      <AlertTriangle class="mt-0.5 h-5 w-5 text-red-500" />
                      <div>
                        <h4 class="font-medium">Cancel Immediately</h4>
                        <p class="text-sm text-muted-foreground">
                          The subscription will be canceled immediately. The customer will lose access to the service right away.
                        </p>
                        <Button variant="destructive" class="mt-2" @click="cancelSubscription(false)">
                          Cancel Immediately
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
                <DialogFooter>
                  <Button variant="outline" @click="$event.target.closest('dialog').close()">
                    Close
                  </Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>
          </div>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Warning message -->
      <div v-if="subscription && subscription.warning" class="mb-6 rounded-lg border border-yellow-500/50 bg-yellow-500/10 p-4 text-yellow-700 dark:text-yellow-400">
        <div class="flex items-center gap-2">
          <AlertTriangle class="h-5 w-5" />
          <p>{{ subscription.warning }}</p>
        </div>
      </div>

      <!-- Subscription details -->
      <div v-if="subscription" class="grid gap-6 md:grid-cols-3">
        <!-- Main info -->
        <div class="md:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Subscription Information</CardTitle>
              <CardDescription>Details about this subscription</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Stripe ID</p>
                    <p class="font-mono">{{ subscription.stripe_id }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                    <span :class="[
                      'rounded-full px-2 py-1 text-xs font-medium',
                      getStatusColor(subscription.status)
                    ]">
                      {{ subscription.status }}
                    </span>
                    <span v-if="subscription.cancel_at_period_end" class="ml-2 text-xs text-muted-foreground">
                      (Cancels at period end)
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Created</p>
                    <p>{{ formatDate(subscription.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Current Period End</p>
                    <p>{{ formatDate(subscription.current_period_end) }}</p>
                  </div>
                  <div v-if="subscription.trial_ends_at">
                    <p class="text-sm font-medium text-muted-foreground">Trial Ends At</p>
                    <p>{{ formatDate(subscription.trial_ends_at) }}</p>
                  </div>
                  <div v-if="subscription.ends_at">
                    <p class="text-sm font-medium text-muted-foreground">Ends At</p>
                    <p>{{ formatDate(subscription.ends_at) }}</p>
                  </div>
                </div>

                <!-- Subscription items -->
                <div class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Subscription Items</h3>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Product</TableHead>
                        <TableHead>Price</TableHead>
                        <TableHead>Quantity</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="item in subscription.items" :key="item.id">
                        <TableCell>
                          <div>
                            <p class="font-medium">{{ item.product.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ item.product.description }}</p>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div>
                            <p class="font-medium">{{ formatCurrency(item.price.amount, item.price.currency) }}</p>
                            <p v-if="item.price.interval" class="text-sm text-muted-foreground">
                              per {{ item.price.interval }}
                            </p>
                          </div>
                        </TableCell>
                        <TableCell>{{ item.quantity }}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>

                <!-- Latest invoice -->
                <div v-if="subscription.latest_invoice" class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Latest Invoice</h3>
                  <div class="rounded-md border p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Invoice ID</p>
                        <p class="font-mono text-xs">{{ subscription.latest_invoice.id }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Amount Due</p>
                        <p>{{ formatCurrency(subscription.latest_invoice.amount_due, subscription.latest_invoice.currency) }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Status</p>
                        <span :class="[
                          'rounded-full px-2 py-1 text-xs font-medium',
                          subscription.latest_invoice.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                        ]">
                          {{ subscription.latest_invoice.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Customer info -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle>Customer</CardTitle>
              <CardDescription>Customer information</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                    <User class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <p class="font-medium">{{ subscription.user.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ subscription.user.email }}</p>
                  </div>
                </div>
                <Button as-child variant="outline" class="w-full">
                  <Link :href="route('admin.stripe.customers.show', subscription.user.id)" class="w-full">
                    View Customer
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
