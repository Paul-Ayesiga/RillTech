<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'vue-sonner';
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, User, Plus, Search } from 'lucide-vue-next';
import DataTableStripeCustomers from '@/components/admin/DataTableStripeCustomers.vue';

const props = defineProps({
  customers: Array,
  potential_customers: Array,
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
    title: 'Customers',
    href: '/admin/stripe/customers',
  },
];

// Search and filters
const search = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);

// Dialog state
const isCreateCustomerDialogOpen = ref(false);

// Create customer form
const createCustomerForm = useForm({
  user_id: '',
});

// Apply filters
const applyFilters = () => {
  router.get(
    route('admin.stripe.customers'),
    {
      search: search.value,
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
watch([search, perPage], () => {
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

// Submit create customer form
const submitCreateCustomer = () => {
  createCustomerForm.post(route('admin.stripe.customers.store'), {
    onSuccess: () => {
      toast.success('Customer created successfully');
      createCustomerForm.reset();
      isCreateCustomerDialogOpen.value = false;
    },
    onError: (errors) => {
      toast.error('Failed to create customer: ' + Object.values(errors).flat().join(', '));
      console.error(errors);
    },
  });
};
</script>

<template>
  <Head title="Stripe Customers" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Stripe Customers</h1>
        <Dialog v-model:open="isCreateCustomerDialogOpen">
          <DialogTrigger asChild>
            <Button class="flex items-center gap-2">
              <Plus class="h-4 w-4" />
              Create Customer
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Create Stripe Customer</DialogTitle>
              <DialogDescription>
                Create a Stripe customer for an existing user.
              </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitCreateCustomer">
              <div class="grid gap-4 py-4">
                <div>
                  <label for="user_id" class="block text-sm font-medium mb-2">User ID</label>
                  <Input id="user_id" v-model="createCustomerForm.user_id" placeholder="Enter user ID" required />
                  <p class="mt-1 text-xs text-muted-foreground">
                    Enter the ID of an existing user who doesn't have a Stripe customer yet.
                  </p>
                  <p v-if="createCustomerForm.errors.user_id" class="mt-1 text-sm text-destructive">
                    {{ createCustomerForm.errors.user_id }}
                  </p>
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" @click="$event.target.closest('dialog').close()">
                  Cancel
                </Button>
                <Button type="submit" :disabled="createCustomerForm.processing">
                  <span v-if="createCustomerForm.processing" class="mr-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  Create Customer
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

      <!-- Customers data table -->
      <Card v-if="customers && customers.length > 0">
        <CardContent>
          <DataTableStripeCustomers :customers="customers" />
        </CardContent>
      </Card>

      <!-- No customers message with potential customers -->
      <Card v-else-if="potential_customers && potential_customers.length > 0">
        <CardContent class="p-6">
          <div class="text-center mb-6">
            <h3 class="text-lg font-medium">No Stripe customers found</h3>
            <p class="text-muted-foreground">We found these users you can convert to Stripe customers:</p>
          </div>

          <div class="rounded-md border">
            <table class="w-full">
              <thead>
                <tr class="border-b bg-muted/50">
                  <th class="h-10 px-4 text-left align-middle font-medium">User</th>
                  <th class="h-10 px-4 text-left align-middle font-medium">Created</th>
                  <th class="h-10 px-4 text-right align-middle font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in potential_customers" :key="user.id" class="border-b">
                  <td class="p-4">
                    <div class="flex items-center gap-2">
                      <User class="h-4 w-4 text-muted-foreground" />
                      <div>
                        <p class="font-medium">{{ user.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="p-4">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                  <td class="p-4 text-right">
                    <Button
                      size="sm"
                      variant="outline"
                      class="flex items-center gap-1"
                      @click="createCustomerForm.user_id = user.id; submitCreateCustomer()"
                    >
                      <Plus class="h-3 w-3" />
                      Convert
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- No customers or potential customers -->
      <Card v-else>
        <CardContent class="p-6 text-center">
          <div class="flex flex-col items-center gap-2 py-8">
            <User class="h-12 w-12 text-muted-foreground/50" />
            <h3 class="text-lg font-medium">No customers found</h3>
            <p class="text-muted-foreground">There are no Stripe customers in the system yet.</p>
            <Button class="mt-4" @click="isCreateCustomerDialogOpen = true">
              <Plus class="h-4 w-4 mr-2" />
              Create Customer
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
