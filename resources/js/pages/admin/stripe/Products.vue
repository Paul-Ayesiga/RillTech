<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'vue-sonner';
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { DollarSign, Plus, Search, AlertCircle, Check, X } from 'lucide-vue-next';

const props = defineProps({
  products: Array,
  pagination: Object,
  filters: Object,
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
    title: 'Products',
    href: '/admin/stripe/products',
  },
];

// Search and filters
const search = ref(props.filters?.search || '');
const active = ref(props.filters?.active || 'all');
const perPage = ref(props.filters?.per_page || 10);

// Dialog state
const isCreateProductDialogOpen = ref(false);

// Create product form
const createProductForm = useForm({
  name: '',
  description: '',
  active: true,
  price_amount: 0,
  price_currency: 'USD',
  price_interval: 'month',
  price_interval_count: 1,
  features: [],
  is_popular: false,
});

// Add feature field
const newFeature = ref({ name: '', included: true });
const addFeature = () => {
  if (newFeature.value.name.trim()) {
    createProductForm.features.push({ ...newFeature.value });
    newFeature.value = { name: '', included: true };
  }
};

// Remove feature
const removeFeature = (index) => {
  createProductForm.features.splice(index, 1);
};

// Submit create product form
const submitCreateProduct = () => {
  // Convert features to the correct format for the backend
  const formattedFeatures = createProductForm.features.map(feature => ({
    name: feature.name,
    included: feature.included
  }));

  createProductForm.post(route('admin.stripe.products.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Product created successfully');
      createProductForm.reset();
      createProductForm.features = [];
      isCreateProductDialogOpen.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to create product: ' + Object.values(errors).flat().join(', '));
      console.error(errors);
    },
  });
};

// Apply filters
const applyFilters = () => {
  router.get(
    route('admin.stripe.products'),
    {
      search: search.value,
      active: active.value,
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
watch([search, active, perPage], () => {
  applyFilters();
});

// Format currency
const formatCurrency = (amount, currency) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(amount);
};

// Format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>

<template>
  <Head title="Stripe Products" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Stripe Products</h1>
        <Dialog v-model:open="isCreateProductDialogOpen">
          <DialogTrigger asChild>
            <Button class="flex items-center gap-2">
              <Plus class="h-4 w-4" />
              Create Product
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
              <DialogTitle>Create New Product</DialogTitle>
              <DialogDescription>
                Create a new product with pricing in Stripe. This will be available for subscriptions.
              </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitCreateProduct">
              <div class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="col-span-2">
                    <div class="mb-2">
                      <label for="name" class="block text-sm font-medium">Product Name</label>
                      <Input id="name" v-model="createProductForm.name" placeholder="Pro Plan" required />
                      <p v-if="createProductForm.errors.name" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.name }}
                      </p>
                    </div>
                  </div>
                  <div class="col-span-2">
                    <div class="mb-2">
                      <label for="description" class="block text-sm font-medium">Description</label>
                      <Textarea id="description" v-model="createProductForm.description" placeholder="For professionals and teams" />
                      <p v-if="createProductForm.errors.description" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.description }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <div class="mb-2">
                      <label for="price_amount" class="block text-sm font-medium">Price Amount</label>
                      <Input id="price_amount" v-model="createProductForm.price_amount" type="number" min="0" step="0.01" required />
                      <p v-if="createProductForm.errors.price_amount" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.price_amount }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <div class="mb-2">
                      <label for="price_currency" class="block text-sm font-medium">Currency</label>
                      <Select v-model="createProductForm.price_currency">
                        <SelectTrigger>
                          <SelectValue placeholder="Select currency" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="USD">USD</SelectItem>
                          <SelectItem value="EUR">EUR</SelectItem>
                          <SelectItem value="GBP">GBP</SelectItem>
                        </SelectContent>
                      </Select>
                      <p v-if="createProductForm.errors.price_currency" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.price_currency }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <div class="mb-2">
                      <label for="price_interval" class="block text-sm font-medium">Billing Interval</label>
                      <Select v-model="createProductForm.price_interval">
                        <SelectTrigger>
                          <SelectValue placeholder="Select interval" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="day">Daily</SelectItem>
                          <SelectItem value="week">Weekly</SelectItem>
                          <SelectItem value="month">Monthly</SelectItem>
                          <SelectItem value="year">Yearly</SelectItem>
                        </SelectContent>
                      </Select>
                      <p v-if="createProductForm.errors.price_interval" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.price_interval }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <div class="mb-2">
                      <label for="price_interval_count" class="block text-sm font-medium">Interval Count</label>
                      <Input id="price_interval_count" v-model="createProductForm.price_interval_count" type="number" min="1" required />
                      <p v-if="createProductForm.errors.price_interval_count" class="mt-1 text-sm text-destructive">
                        {{ createProductForm.errors.price_interval_count }}
                      </p>
                    </div>
                  </div>
                  <div class="col-span-2">
                    <div class="flex items-center space-x-2">
                      <Checkbox id="active" v-model="createProductForm.active" />
                      <label for="active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Active
                      </label>
                    </div>
                  </div>
                  <div class="col-span-2">
                    <div class="flex items-center space-x-2">
                      <Checkbox id="is_popular" v-model="createProductForm.is_popular" />
                      <label for="is_popular" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Mark as Popular
                      </label>
                    </div>
                  </div>
                  <div class="col-span-2">
                    <div class="mb-2">
                      <label class="block text-sm font-medium">Features</label>
                      <div class="mb-2 space-y-2">
                        <div v-for="(feature, index) in createProductForm.features" :key="index" class="flex items-center gap-2">
                          <div class="flex-1">{{ feature.name }}</div>
                          <div class="flex items-center gap-1">
                            <span v-if="feature.included" class="text-green-600">
                              <Check class="h-4 w-4" />
                            </span>
                            <span v-else class="text-red-600">
                              <X class="h-4 w-4" />
                            </span>
                          </div>
                          <Button type="button" variant="ghost" size="icon" @click="removeFeature(index)">
                            <X class="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                      <div class="flex gap-2">
                        <div class="flex-1">
                          <Input v-model="newFeature.name" placeholder="Feature name" />
                        </div>
                        <div class="flex items-center space-x-2">
                          <Checkbox id="feature_included" v-model="newFeature.included" />
                          <label for="feature_included" class="text-sm font-medium leading-none">
                            Included
                          </label>
                        </div>
                        <Button type="button" variant="outline" @click="addFeature">Add</Button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" @click="isCreateProductDialogOpen = false">
                  Cancel
                </Button>
                <Button type="submit" :disabled="createProductForm.processing">
                  <span v-if="createProductForm.processing" class="mr-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  Create Product
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Error message -->
      <div v-if="error" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex items-center gap-2">
          <AlertCircle class="h-5 w-5" />
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
          <div class="relative">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="search"
              placeholder="Search products..."
              class="pl-8"
            />
          </div>
        </div>
        <Select v-model="active" class="w-full sm:w-[150px]">
          <SelectTrigger>
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All</SelectItem>
            <SelectItem value="true">Active</SelectItem>
            <SelectItem value="false">Inactive</SelectItem>
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

      <!-- Products table -->
      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="product in products" :key="product.id">
                <TableCell>
                  <div>
                    <p class="font-medium">{{ product.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ product.description }}</p>
                  </div>
                </TableCell>
                <TableCell>
                  <div v-if="product.default_price">
                    <p class="font-medium">{{ formatCurrency(product.default_price.amount, product.default_price.currency) }}</p>
                    <p v-if="product.default_price.interval" class="text-sm text-muted-foreground">
                      per {{ product.default_price.interval }}
                    </p>
                  </div>
                  <p v-else class="text-sm text-muted-foreground">No price</p>
                </TableCell>
                <TableCell>
                  <span :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    product.active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                  ]">
                    {{ product.active ? 'Active' : 'Inactive' }}
                  </span>
                </TableCell>
                <TableCell>{{ formatDate(product.created) }}</TableCell>
                <TableCell class="text-right">
                  <Button as-child variant="ghost" size="sm">
                    <Link :href="route('admin.stripe.products.show', product.id)">
                      View
                    </Link>
                  </Button>
                </TableCell>
              </TableRow>
              <TableRow v-if="products && products.length === 0">
                <TableCell colspan="5" class="h-24 text-center">
                  No products found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
        <CardFooter class="flex items-center justify-between border-t p-4">
          <div class="text-sm text-muted-foreground">
            Showing {{ products ? products.length : 0 }} results
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="!pagination || pagination.current_page <= 1"
              @click="router.get(route('admin.stripe.products'), { page: pagination.current_page - 1, last_id: null, ...filters }, { preserveState: true })"
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              :disabled="!pagination || !pagination.has_more"
              @click="router.get(route('admin.stripe.products'), { page: pagination.current_page + 1, last_id: pagination.last_id, ...filters }, { preserveState: true })"
            >
              Next
            </Button>
          </div>
        </CardFooter>
      </Card>
    </div>
  </AdminLayout>
</template>
