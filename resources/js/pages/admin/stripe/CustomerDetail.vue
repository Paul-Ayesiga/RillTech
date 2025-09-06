<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, User, CreditCard, Calendar, Clock, CheckCircle, XCircle, AlertTriangle, Trash2 } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
  customer: Object,
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
    title: 'Customers',
    href: '/admin/stripe/customers',
  },
  {
    title: props.customer ? props.customer.name : 'Customer Detail',
    href: props.customer ? `/admin/stripe/customers/${props.customer.id}` : '/admin/stripe/customers',
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
// Dialog state for delete confirmation
const isDeleteDialogOpen = ref(false);

// Delete customer function
const deleteCustomer = () => {
  if (!props.customer) return;

  router.delete(route('admin.stripe.customers.destroy', props.customer.id), {
    onSuccess: () => {
      toast.success('Customer deleted successfully');
      // Dialog will close automatically as we're redirected
    },
    onError: (errors) => {
      toast.error('Failed to delete customer: ' + Object.values(errors).flat().join(', '));
      isDeleteDialogOpen.value = false;
    },
  });
};

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
  <Head title="Customer Detail" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Customer Detail</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('admin.stripe.customers')">
              Back to Customers
            </Link>
          </Button>
          <Button as-child variant="outline">
            <a :href="`https://dashboard.stripe.com/customers/${customer?.stripe_id}`" target="_blank" class="flex items-center gap-2">
              View in Stripe
            </a>
          </Button>

          <!-- Delete Customer Button and Dialog -->
          <Dialog v-model:open="isDeleteDialogOpen">
            <DialogTrigger asChild>
              <Button variant="destructive" class="flex items-center gap-2">
                <Trash2 class="h-4 w-4" />
                Delete Customer
              </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-[425px]">
              <DialogHeader>
                <DialogTitle>Delete Stripe Customer</DialogTitle>
                <DialogDescription>
                  Are you sure you want to delete this Stripe customer? This will remove the Stripe connection from the user account.
                </DialogDescription>
              </DialogHeader>
              <div class="py-4">
                <div class="rounded-md border border-destructive/20 bg-destructive/10 p-4 text-destructive">
                  <div class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5" />
                    <p>This action cannot be undone. The user will remain in the system but will no longer be a Stripe customer.</p>
                  </div>
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" @click="isDeleteDialogOpen = false">
                  Cancel
                </Button>
                <Button variant="destructive" @click="deleteCustomer">
                  Delete Customer
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Customer details -->
      <div v-if="customer" class="grid gap-6 md:grid-cols-3">
        <!-- Main info -->
        <div class="md:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Customer Information</CardTitle>
              <CardDescription>Details about this customer</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                    <User class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <p class="font-medium">{{ customer.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ customer.email }}</p>
                  </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Stripe ID</p>
                    <p class="font-mono">{{ customer.stripe_id }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Created</p>
                    <p>{{ formatDate(customer.created_at) }}</p>
                  </div>
                  <div v-if="customer.trial_ends_at">
                    <p class="text-sm font-medium text-muted-foreground">Trial Ends At</p>
                    <p>{{ formatDate(customer.trial_ends_at) }}</p>
                  </div>
                  <div v-if="customer.pm_type && customer.pm_last_four">
                    <p class="text-sm font-medium text-muted-foreground">Default Payment Method</p>
                    <div class="flex items-center gap-2">
                      <CreditCard class="h-4 w-4 text-muted-foreground" />
                      <span>{{ customer.pm_type }} •••• {{ customer.pm_last_four }}</span>
                    </div>
                  </div>
                </div>

                <!-- Stripe details -->
                <div v-if="customer.stripe_details" class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Stripe Details</h3>
                  <div class="rounded-md border p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                      <div v-if="customer.stripe_details.balance !== undefined">
                        <p class="text-sm font-medium text-muted-foreground">Balance</p>
                        <p>{{ formatCurrency(customer.stripe_details.balance, customer.stripe_details.currency) }}</p>
                      </div>
                      <div v-if="customer.stripe_details.currency">
                        <p class="text-sm font-medium text-muted-foreground">Currency</p>
                        <p>{{ customer.stripe_details.currency?.toUpperCase() }}</p>
                      </div>
                      <div v-if="customer.stripe_details.delinquent !== undefined">
                        <p class="text-sm font-medium text-muted-foreground">Delinquent</p>
                        <span :class="[
                          'rounded-full px-2 py-1 text-xs font-medium',
                          customer.stripe_details.delinquent ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                        ]">
                          {{ customer.stripe_details.delinquent ? 'Yes' : 'No' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Payment methods -->
                <div v-if="customer.payment_methods && customer.payment_methods.length > 0" class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Payment Methods</h3>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Type</TableHead>
                        <TableHead>Details</TableHead>
                        <TableHead>Expires</TableHead>
                        <TableHead>Default</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="method in customer.payment_methods" :key="method.id">
                        <TableCell class="capitalize">{{ method.type }}</TableCell>
                        <TableCell>
                          <div v-if="method.brand && method.last4" class="flex items-center gap-2">
                            <CreditCard class="h-4 w-4 text-muted-foreground" />
                            <span class="capitalize">{{ method.brand }} •••• {{ method.last4 }}</span>
                          </div>
                          <span v-else class="text-sm text-muted-foreground">N/A</span>
                        </TableCell>
                        <TableCell>
                          <span v-if="method.exp_month && method.exp_year">
                            {{ method.exp_month }}/{{ method.exp_year }}
                          </span>
                          <span v-else class="text-sm text-muted-foreground">N/A</span>
                        </TableCell>
                        <TableCell>
                          <CheckCircle v-if="method.is_default" class="h-4 w-4 text-green-500" />
                          <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Subscriptions -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle>Subscriptions</CardTitle>
              <CardDescription>Customer's subscriptions</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="customer.subscriptions && customer.subscriptions.length > 0" class="space-y-4">
                <div v-for="subscription in customer.subscriptions" :key="subscription.id" class="rounded-md border p-4">
                  <div class="mb-2 flex items-center justify-between">
                    <span :class="[
                      'rounded-full px-2 py-1 text-xs font-medium',
                      getStatusColor(subscription.status)
                    ]">
                      {{ subscription.status }}
                    </span>
                    <span v-if="subscription.cancel_at_period_end" class="text-xs text-muted-foreground">
                      Cancels at period end
                    </span>
                  </div>
                  <p class="mb-1 font-mono text-xs">{{ subscription.id }}</p>
                  <p class="mb-2 text-sm text-muted-foreground">
                    Renews: {{ formatDate(subscription.current_period_end) }}
                  </p>
                  <div class="space-y-2">
                    <div v-for="item in subscription.items" :key="item.id" class="text-sm">
                      <div class="flex items-center justify-between">
                        <span>{{ item.price.amount }} {{ item.price.currency }}</span>
                        <span>x{{ item.quantity }}</span>
                      </div>
                      <p class="text-xs text-muted-foreground">
                        per {{ item.price.interval }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="py-4 text-center text-muted-foreground">
                No subscriptions found
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
