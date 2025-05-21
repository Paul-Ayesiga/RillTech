<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { CreditCard, DollarSign, Users, BarChart, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
  account: Object,
  products: Object,
  customers: Object,
  subscriptions: Object,
  balance: Array,
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
];
</script>

<template>
  <Head title="Stripe Dashboard" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Stripe Dashboard</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <a href="https://dashboard.stripe.com" target="_blank" class="flex items-center gap-2">
              <BarChart class="h-4 w-4" />
              Open Stripe Dashboard
            </a>
          </Button>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Account information -->
      <div v-if="account && !error" class="mb-6">
        <Card>
          <CardHeader>
            <CardTitle>Account Information</CardTitle>
            <CardDescription>Your Stripe account details</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Business Name</p>
                <p>{{ account.business_name }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Account ID</p>
                <p>{{ account.id }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Email</p>
                <p>{{ account.email }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-muted-foreground">Country</p>
                <p>{{ account.country }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Dashboard overview -->
      <div v-if="!error" class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <!-- Products card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Total Products</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <div class="text-2xl font-bold">{{ products.total }}</div>
              <DollarSign class="h-8 w-8 text-muted-foreground" />
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="ghost" class="w-full">
              <Link :href="route('admin.stripe.products')" class="w-full">View All Products</Link>
            </Button>
          </CardFooter>
        </Card>

        <!-- Customers card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Total Customers</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <div class="text-2xl font-bold">{{ customers.total }}</div>
              <Users class="h-8 w-8 text-muted-foreground" />
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="ghost" class="w-full">
              <Link :href="route('admin.stripe.customers')" class="w-full">View All Customers</Link>
            </Button>
          </CardFooter>
        </Card>

        <!-- Subscriptions card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Active Subscriptions</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <div class="text-2xl font-bold">{{ subscriptions.total }}</div>
              <CreditCard class="h-8 w-8 text-muted-foreground" />
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="ghost" class="w-full">
              <Link :href="route('admin.stripe.subscriptions')" class="w-full">View All Subscriptions</Link>
            </Button>
          </CardFooter>
        </Card>

        <!-- Balance card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Available Balance</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-between">
              <div v-if="balance && balance.length > 0" class="text-2xl font-bold">
                {{ balance[0].currency }} {{ balance[0].amount.toFixed(2) }}
              </div>
              <div v-else class="text-2xl font-bold">$0.00</div>
              <BarChart class="h-8 w-8 text-muted-foreground" />
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="ghost" class="w-full">
              <a href="https://dashboard.stripe.com/balance" target="_blank" class="w-full">View in Stripe</a>
            </Button>
          </CardFooter>
        </Card>
      </div>

      <!-- Recent data -->
      <div v-if="!error" class="mt-6 grid gap-6 md:grid-cols-2">
        <!-- Recent products -->
        <Card>
          <CardHeader>
            <CardTitle>Recent Products</CardTitle>
            <CardDescription>Your most recently created products</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="products.recent && products.recent.length > 0" class="space-y-4">
              <div v-for="product in products.recent" :key="product.id" class="flex items-center justify-between">
                <div>
                  <p class="font-medium">{{ product.name }}</p>
                  <p class="text-sm text-muted-foreground">Created: {{ product.created }}</p>
                </div>
                <div>
                  <span :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    product.active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                  ]">
                    {{ product.active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="py-4 text-center text-muted-foreground">
              No products found
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="outline" class="w-full">
              <Link :href="route('admin.stripe.products')" class="w-full">View All Products</Link>
            </Button>
          </CardFooter>
        </Card>

        <!-- Recent subscriptions -->
        <Card>
          <CardHeader>
            <CardTitle>Recent Subscriptions</CardTitle>
            <CardDescription>Your most recent active subscriptions</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="subscriptions.recent && subscriptions.recent.length > 0" class="space-y-4">
              <div v-for="subscription in subscriptions.recent" :key="subscription.id" class="flex items-center justify-between">
                <div>
                  <p class="font-medium">{{ subscription.id }}</p>
                  <p class="text-sm text-muted-foreground">Ends: {{ subscription.current_period_end }}</p>
                </div>
                <div>
                  <span :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    subscription.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                  ]">
                    {{ subscription.status }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="py-4 text-center text-muted-foreground">
              No subscriptions found
            </div>
          </CardContent>
          <CardFooter>
            <Button as-child variant="outline" class="w-full">
              <Link :href="route('admin.stripe.subscriptions')" class="w-full">View All Subscriptions</Link>
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
