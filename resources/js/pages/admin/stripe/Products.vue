<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, AlertCircle, Check, X } from 'lucide-vue-next';
import DataTableStripeProducts from '@/components/admin/DataTableStripeProducts.vue';

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

// These functions are now handled by the DataTable component
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

      <!-- Products DataTable -->
      <Card>
        <CardHeader>
          <CardTitle>Stripe Products</CardTitle>
          <CardDescription>
            Manage and view all Stripe products with advanced filtering and sorting.
          </CardDescription>
        </CardHeader>
        <CardContent class="px-3">
          <DataTableStripeProducts :products="products || []" />
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
