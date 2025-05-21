<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, DollarSign, Plus, Check, X, Pencil, Trash2, AlertTriangle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const props = defineProps({
  product: Object,
  prices: Array,
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
  {
    title: props.product ? props.product.name : 'Product Detail',
    href: props.product ? `/admin/stripe/products/${props.product.id}` : '/admin/stripe/products',
  },
];

// Update product form
const updateProductForm = useForm({
  name: props.product?.name || '',
  description: props.product?.description || '',
  active: props.product?.active || true,
  features: [],
  is_popular: props.product?.metadata?.is_popular || false,
});

// Create price form
const createPriceForm = useForm({
  amount: 0,
  currency: 'USD',
  interval: 'month',
  interval_count: 1,
  is_default: false,
});

// Edit price form
const editPriceForm = useForm({
  is_default: false,
  active: true,
  create_new: false,
  amount: 0,
  currency: 'USD',
  interval: 'month',
  interval_count: 1,
});

// Price to edit
const priceToEdit = ref(null);
const isEditPriceDialogOpen = ref(false);

// Open edit price dialog
const openEditPriceDialog = (price) => {
  priceToEdit.value = price;
  editPriceForm.is_default = price.is_default;
  editPriceForm.active = price.active !== false; // Default to true if not specified
  editPriceForm.create_new = false;
  editPriceForm.amount = price.amount;
  editPriceForm.currency = price.currency;
  editPriceForm.interval = price.interval || 'month';
  editPriceForm.interval_count = price.interval_count || 1;
  isEditPriceDialogOpen.value = true;
};

// Submit edit price form
const submitEditPrice = () => {
  if (!priceToEdit.value) return;

  // Use the correct route parameter names
  editPriceForm.put(`/admin/stripe/products/${props.product.id}/prices/${priceToEdit.value.id}`, {
    onSuccess: () => {
      toast.success('Price updated successfully');
      isEditPriceDialogOpen.value = false;
      priceToEdit.value = null;
    },
    onError: (errors) => {
      toast.error('Failed to update price');
      console.error(errors);
    },
  });
};

// Price to delete
const priceToDelete = ref(null);
const isDeletePriceDialogOpen = ref(false);

// Open delete price dialog
const openDeletePriceDialog = (price) => {
  priceToDelete.value = price;
  isDeletePriceDialogOpen.value = true;
};

// Delete price
const deletePrice = () => {
  if (!priceToDelete.value) return;

  // Use the correct route with direct URL
  useForm({}).delete(`/admin/stripe/products/${props.product.id}/prices/${priceToDelete.value.id}`, {
    onSuccess: () => {
      toast.success('Price archived successfully');
      isDeletePriceDialogOpen.value = false;
      priceToDelete.value = null;
    },
    onError: () => {
      toast.error('Failed to archive price');
    },
  });
};

// Add feature field
const newFeature = ref({ name: '', included: true });
const addFeature = () => {
  if (newFeature.value.name.trim()) {
    updateProductForm.features.push({ ...newFeature.value });
    newFeature.value = { name: '', included: true };
  }
};

// Remove feature
const removeFeature = (index) => {
  updateProductForm.features.splice(index, 1);
};

// Submit update product form
const submitUpdateProduct = () => {
  updateProductForm.put(route('admin.stripe.products.update', props.product.id), {
    onSuccess: () => {
      toast.success('Product updated successfully');
      document.getElementById('close-dialog')?.click();
    },
    onError: (errors) => {
      toast.error('Failed to update product');
      console.error(errors);
    },
  });
};

// Create price dialog state
const isCreatePriceDialogOpen = ref(false);

// Submit create price form
const submitCreatePrice = () => {
  createPriceForm.post(`/admin/stripe/products/${props.product.id}/prices`, {
    onSuccess: () => {
      toast.success('Price created successfully');
      createPriceForm.reset();
      isCreatePriceDialogOpen.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to create price');
      console.error(errors);
    },
  });
};

// Format currency
const formatCurrency = (amount, currency) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(amount);
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Initialize features from product metadata
if (props.product && props.product.metadata && props.product.metadata.features) {
  try {
    const features = JSON.parse(props.product.metadata.features);
    updateProductForm.features = Array.isArray(features) ? features : [];
  } catch (e) {
    console.error('Error parsing features:', e);
    updateProductForm.features = [];
  }
}
</script>

<template>
  <Head title="Product Detail" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Product Detail</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('admin.stripe.products')">
              Back to Products
            </Link>
          </Button>
          <Dialog>
            <DialogTrigger asChild>
              <Button>Edit Product</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-[600px]">
              <DialogHeader>
                <DialogTitle>Edit Product</DialogTitle>
                <DialogDescription>
                  Update the product details in Stripe.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="submitUpdateProduct">
                <div class="grid gap-4 py-4">
                  <div>
                    <label for="name" class="block text-sm font-medium mb-2">Product Name</label>
                    <Input id="name" v-model="updateProductForm.name" placeholder="Pro Plan" required />
                    <p v-if="updateProductForm.errors.name" class="mt-1 text-sm text-destructive">
                      {{ updateProductForm.errors.name }}
                    </p>
                  </div>
                  <div>
                    <label for="description" class="block text-sm font-medium mb-2">Description</label>
                    <Textarea id="description" v-model="updateProductForm.description" placeholder="For professionals and teams" />
                    <p v-if="updateProductForm.errors.description" class="mt-1 text-sm text-destructive">
                      {{ updateProductForm.errors.description }}
                    </p>
                  </div>
                  <div>
                    <div class="flex items-center space-x-2">
                      <Checkbox id="active" v-model="updateProductForm.active" />
                      <label for="active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Active
                      </label>
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center space-x-2">
                      <Checkbox id="is_popular" v-model="updateProductForm.is_popular" />
                      <label for="is_popular" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Mark as Popular
                      </label>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-2">Features</label>
                    <div class="mb-2 space-y-2">
                      <div v-for="(feature, index) in updateProductForm.features" :key="index" class="flex items-center gap-2">
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
                <DialogFooter>
                  <Button type="button" variant="outline" id="close-dialog" @click="$event.target.closest('dialog').close()">
                    Cancel
                  </Button>
                  <Button type="submit" :disabled="updateProductForm.processing">
                    <span v-if="updateProductForm.processing" class="mr-2">
                      <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </span>
                    Update Product
                  </Button>
                </DialogFooter>
              </form>
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

      <!-- Product details -->
      <div v-if="product" class="grid gap-6 md:grid-cols-3">
        <!-- Main info -->
        <div class="md:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Product Information</CardTitle>
              <CardDescription>Details about this product</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Product ID</p>
                    <p class="font-mono">{{ product.id }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                    <span :class="[
                      'rounded-full px-2 py-1 text-xs font-medium',
                      product.active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                    ]">
                      {{ product.active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Name</p>
                    <p>{{ product.name }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Created</p>
                    <p>{{ formatDate(product.created) }}</p>
                  </div>
                  <div class="md:col-span-2">
                    <p class="text-sm font-medium text-muted-foreground">Description</p>
                    <p>{{ product.description || 'No description' }}</p>
                  </div>
                </div>

                <!-- Default price -->
                <div v-if="product.default_price" class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Default Price</h3>
                  <div class="rounded-md border p-4">
                    <div class="grid gap-4 md:grid-cols-3">
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Amount</p>
                        <p class="font-medium">{{ formatCurrency(product.default_price.amount, product.default_price.currency) }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Interval</p>
                        <p class="capitalize">{{ product.default_price.interval || 'One-time' }}</p>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-muted-foreground">Type</p>
                        <p class="capitalize">{{ product.default_price.type }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Features -->
                <div v-if="updateProductForm.features && updateProductForm.features.length > 0" class="mt-6">
                  <h3 class="mb-2 text-lg font-medium">Features</h3>
                  <ul class="space-y-2">
                    <li v-for="(feature, index) in updateProductForm.features" :key="index" class="flex items-center gap-2">
                      <span v-if="feature.included" class="text-green-600">
                        <Check class="h-4 w-4" />
                      </span>
                      <span v-else class="text-red-600">
                        <X class="h-4 w-4" />
                      </span>
                      <span>{{ feature.name }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Metadata -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle>Metadata</CardTitle>
              <CardDescription>Additional product information</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-if="product.metadata && Object.keys(product.metadata).length > 0">
                  <div v-for="(value, key) in product.metadata" :key="key" class="mb-2">
                    <p class="text-sm font-medium text-muted-foreground">{{ key }}</p>
                    <p class="break-all">{{ value }}</p>
                  </div>
                </div>
                <div v-else class="py-4 text-center text-muted-foreground">
                  No metadata found
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Prices -->
      <div class="mt-6">
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Prices</CardTitle>
                <CardDescription>All prices for this product</CardDescription>
              </div>
              <Dialog v-model:open="isCreatePriceDialogOpen">
                <DialogTrigger asChild>
                  <Button class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Add Price
                  </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                  <DialogHeader>
                    <DialogTitle>Create New Price</DialogTitle>
                    <DialogDescription>
                      Create a new price for this product.
                    </DialogDescription>
                  </DialogHeader>
                  <form @submit.prevent="submitCreatePrice">
                    <div class="grid gap-4 py-4">
                      <div>
                        <label for="amount" class="block text-sm font-medium mb-2">Amount</label>
                        <Input id="amount" v-model="createPriceForm.amount" type="number" min="0" step="0.01" required />
                        <p v-if="createPriceForm.errors.amount" class="mt-1 text-sm text-destructive">
                          {{ createPriceForm.errors.amount }}
                        </p>
                      </div>
                      <div>
                        <label for="currency" class="block text-sm font-medium mb-2">Currency</label>
                        <Select v-model="createPriceForm.currency">
                          <SelectTrigger>
                            <SelectValue placeholder="Select currency" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="USD">USD</SelectItem>
                            <SelectItem value="EUR">EUR</SelectItem>
                            <SelectItem value="GBP">GBP</SelectItem>
                          </SelectContent>
                        </Select>
                        <p v-if="createPriceForm.errors.currency" class="mt-1 text-sm text-destructive">
                          {{ createPriceForm.errors.currency }}
                        </p>
                      </div>
                      <div>
                        <label for="interval" class="block text-sm font-medium mb-2">Billing Interval</label>
                        <Select v-model="createPriceForm.interval">
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
                        <p v-if="createPriceForm.errors.interval" class="mt-1 text-sm text-destructive">
                          {{ createPriceForm.errors.interval }}
                        </p>
                      </div>
                      <div>
                        <label for="interval_count" class="block text-sm font-medium mb-2">Interval Count</label>
                        <Input id="interval_count" v-model="createPriceForm.interval_count" type="number" min="1" required />
                        <p v-if="createPriceForm.errors.interval_count" class="mt-1 text-sm text-destructive">
                          {{ createPriceForm.errors.interval_count }}
                        </p>
                      </div>
                      <div>
                        <div class="flex items-center space-x-2">
                          <Checkbox id="is_default" v-model="createPriceForm.is_default" />
                          <label for="is_default" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            Set as default price
                          </label>
                        </div>
                      </div>
                    </div>
                    <DialogFooter>
                      <Button type="button" variant="outline" @click="isCreatePriceDialogOpen = false">
                        Cancel
                      </Button>
                      <Button type="submit" :disabled="createPriceForm.processing">
                        <span v-if="createPriceForm.processing" class="mr-2">
                          <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </span>
                        Create Price
                      </Button>
                    </DialogFooter>
                  </form>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Amount</TableHead>
                  <TableHead>Interval</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead>Default</TableHead>
                  <TableHead>Active</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="price in prices" :key="price.id">
                  <TableCell>{{ formatCurrency(price.amount, price.currency) }}</TableCell>
                  <TableCell>
                    <span v-if="price.interval" class="capitalize">
                      {{ price.interval_count > 1 ? `${price.interval_count} ${price.interval}s` : price.interval }}
                    </span>
                    <span v-else>One-time</span>
                  </TableCell>
                  <TableCell class="capitalize">{{ price.type }}</TableCell>
                  <TableCell>{{ formatDate(price.created) }}</TableCell>
                  <TableCell>
                    <Check v-if="price.is_default" class="h-4 w-4 text-green-500" />
                    <X v-else class="h-4 w-4 text-muted-foreground" />
                  </TableCell>
                  <TableCell>
                    <Check v-if="price.active !== false" class="h-4 w-4 text-green-500" />
                    <X v-else class="h-4 w-4 text-destructive" />
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex justify-end gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        @click="openEditPriceDialog(price)"
                        title="Edit Price"
                      >
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        @click="openDeletePriceDialog(price)"
                        title="Archive Price"
                        :disabled="price.is_default"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
                <TableRow v-if="prices && prices.length === 0">
                  <TableCell colspan="7" class="h-24 text-center">
                    No prices found.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <!-- Edit Price Dialog -->
      <Dialog
        v-model:open="isEditPriceDialogOpen"
        @update:open="(open) => !open && (priceToEdit = null)"
      >
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Edit Price</DialogTitle>
            <DialogDescription>
              Update price settings for this product.
            </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitEditPrice">
            <div class="grid gap-4 py-4">
              <div v-if="priceToEdit">
                <div class="mb-4 p-3 bg-muted/30 rounded-md">
                  <h4 class="text-sm font-medium mb-1">Current Price</h4>
                  <p class="font-medium">{{ formatCurrency(priceToEdit.amount, priceToEdit.currency) }}</p>
                  <p class="text-sm text-muted-foreground">
                    <span v-if="priceToEdit.interval" class="capitalize">
                      {{ priceToEdit.interval_count > 1 ? `${priceToEdit.interval_count} ${priceToEdit.interval}s` : priceToEdit.interval }}
                    </span>
                    <span v-else>One-time</span>
                  </p>
                </div>
              </div>

              <div class="mt-2 space-y-4">
                <div>
                  <div class="flex items-center space-x-2">
                    <Checkbox id="edit_create_new" v-model="editPriceForm.create_new" />
                    <label for="edit_create_new" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                      Create new price
                    </label>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">
                    Stripe doesn't allow editing existing prices. This will create a new price and archive the old one.
                  </p>
                </div>

                <div v-if="editPriceForm.create_new">
                  <div class="mb-3">
                    <label for="edit_amount" class="block text-sm font-medium mb-2">New Amount</label>
                    <Input id="edit_amount" v-model="editPriceForm.amount" type="number" min="0" step="0.01" required />
                    <p v-if="editPriceForm.errors.amount" class="mt-1 text-sm text-destructive">
                      {{ editPriceForm.errors.amount }}
                    </p>
                  </div>
                </div>

                <div>
                  <div class="flex items-center space-x-2">
                    <Checkbox id="edit_is_default" v-model="editPriceForm.is_default" />
                    <label for="edit_is_default" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                      Set as default price
                    </label>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">
                    This will make this price the default for this product.
                  </p>
                </div>

                <div v-if="!editPriceForm.create_new">
                  <div class="flex items-center space-x-2">
                    <Checkbox id="edit_active" v-model="editPriceForm.active" />
                    <label for="edit_active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                      Active
                    </label>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">
                    Inactive prices cannot be used for new subscriptions.
                  </p>
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" @click="isEditPriceDialogOpen = false">
                Cancel
              </Button>
              <Button type="submit" :disabled="editPriceForm.processing">
                <span v-if="editPriceForm.processing" class="mr-2">
                  <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </span>
                Update Price
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Delete Price Confirmation Dialog -->
      <Dialog
        v-model:open="isDeletePriceDialogOpen"
        @update:open="(open) => !open && (priceToDelete = null)"
      >
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle class="text-xl flex items-center gap-2">
              <AlertTriangle class="h-5 w-5 text-destructive" />
              Archive Price
            </DialogTitle>
            <DialogDescription>
              Are you sure you want to archive this price? This will make it unavailable for new subscriptions.
            </DialogDescription>
          </DialogHeader>
          <div class="py-4">
            <Alert variant="destructive" class="mb-4">
              <AlertTriangle class="h-4 w-4" />
              <AlertTitle>Warning</AlertTitle>
              <AlertDescription>
                Archiving a price will not affect existing subscriptions using this price, but it will no longer be available for new subscriptions.
              </AlertDescription>
            </Alert>

            <div v-if="priceToDelete" class="mt-4">
              <p class="font-medium">{{ formatCurrency(priceToDelete.amount, priceToDelete.currency) }}</p>
              <p class="text-sm text-muted-foreground">
                <span v-if="priceToDelete.interval" class="capitalize">
                  {{ priceToDelete.interval_count > 1 ? `${priceToDelete.interval_count} ${priceToDelete.interval}s` : priceToDelete.interval }}
                </span>
                <span v-else>One-time</span>
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="isDeletePriceDialogOpen = false">
              Cancel
            </Button>
            <Button variant="destructive" @click="deletePrice">
              Archive Price
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AdminLayout>
</template>
