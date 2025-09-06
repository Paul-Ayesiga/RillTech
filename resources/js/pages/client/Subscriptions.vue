<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  CreditCard,
  Calendar,
  Download,
  Settings,
  AlertTriangle,
  CheckCircle,
  XCircle,
  Clock,
  RefreshCw,
  Trash2,
  Plus,
  Loader2
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import axios from 'axios';

// Simple Stripe type declaration
declare global {
  interface Window {
    Stripe: any;
  }
}

// Safe Stripe loading function
const loadStripe = (): Promise<any> => {
  return new Promise((resolve, reject) => {
    // If Stripe is already loaded, return it
    if (window.Stripe) {
      resolve(window.Stripe);
      return;
    }

    // Check if script is already being loaded
    const existingScript = document.querySelector('script[src="https://js.stripe.com/v3/"]');
    if (existingScript) {
      existingScript.addEventListener('load', () => {
        if (window.Stripe) {
          resolve(window.Stripe);
        } else {
          reject(new Error('Stripe failed to load'));
        }
      });
      existingScript.addEventListener('error', () => {
        reject(new Error('Failed to load Stripe script'));
      });
      return;
    }

    // Create and load the script
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = true;

    script.onload = () => {
      if (window.Stripe) {
        resolve(window.Stripe);
      } else {
        reject(new Error('Stripe failed to initialize'));
      }
    };

    script.onerror = () => {
      reject(new Error('Failed to load Stripe script'));
    };

    document.head.appendChild(script);
  });
};

interface Props {
  subscription?: {
    id: number;
    stripe_id: string;
    status: string;
    current_period_start: string;
    current_period_end: string;
    cancel_at_period_end: boolean;
    product_name: string;
    amount: number;
    currency: string;
    interval: string;
    current_price_id: string;
    is_cancelled: boolean;
    on_grace_period: boolean;
    ends_at: string | null;
  };
  availablePlans?: Array<{
    price_id: string;
    product_name: string;
    amount: number;
    currency: string;
    interval: string;
    is_current: boolean;
  }>;
  user?: any;
  hasActiveSubscription?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  subscription: undefined,
  availablePlans: () => [],
  user: undefined,
  hasActiveSubscription: false
});

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Subscriptions', href: '/subscriptions' }
];

// Reactive data
const activeTab = ref('invoices'); // Default to invoices tab
const isLoading = ref(false);
const isLoadingInvoices = ref(false);
const isLoadingPaymentMethods = ref(false);
const isPlanSwitching = ref(false);
const isDeletingPaymentMethod = ref(false);
const isSettingDefault = ref(false);
const isBulkDownloading = ref(false);
const invoices = ref([]);
const paymentMethods = ref([]);
const selectedInvoices = ref([]);

// Modal states
const showCancelModal = ref(false);
const showAddPaymentModal = ref(false);
const showDeletePaymentModal = ref(false);
const showPlanSwitchModal = ref(false);
const selectedPaymentMethodId = ref('');
const selectedPlan = ref(null);
const cancelOption = ref('at_period_end'); // 'at_period_end' or 'immediately'

// Enhanced cancellation and resumption
const isCancelling = ref(false);
const isResuming = ref(false);
const cancelType = ref('end_of_period');
const cancelAtDate = ref('');

// Stripe Elements state
const isAddingPaymentMethod = ref(false);
const stripeElements = ref(null);
const cardElement = ref(null);
const stripeInstance = ref(null); // Store the Stripe instance

// Computed properties
const subscriptionStatus = computed(() => {
  if (!props.subscription) return 'none';
  return props.subscription.status;
});

const statusColor = computed(() => {
  switch (subscriptionStatus.value) {
    case 'active': return 'text-green-600';
    case 'canceled': return 'text-red-600';
    case 'past_due': return 'text-yellow-600';
    case 'unpaid': return 'text-red-600';
    default: return 'text-gray-600';
  }
});

const statusIcon = computed(() => {
  switch (subscriptionStatus.value) {
    case 'active': return CheckCircle;
    case 'canceled': return XCircle;
    case 'past_due': return AlertTriangle;
    case 'unpaid': return AlertTriangle;
    default: return Clock;
  }
});

const formattedAmount = computed(() => {
  if (!props.subscription) return '';
  const amount = props.subscription.amount / 100;
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: props.subscription.currency.toUpperCase()
  }).format(amount);
});

// Check if a payment method can be deleted
const canDeletePaymentMethod = (method: any) => {
  // If user doesn't have an active subscription, they can delete any payment method
  if (!props.hasActiveSubscription) return true;

  // If user has active subscription, they cannot delete the default payment method
  return !method.is_default;
};

// Methods
const handleBillingPortal = () => {
  // Use window.location.href for proper browser redirect (no CORS issues)
  window.location.href = route('subscriptions.billing-portal');
};

const handleCancelSubscription = async () => {
  showCancelModal.value = true;
};

const confirmCancelSubscription = async () => {
  isCancelling.value = true;
  try {
    await axios.post(route('subscriptions.cancel'), {
      cancel_type: cancelType.value,
      cancel_at: cancelAtDate.value || null
    });

    toast('Subscription cancellation processed successfully');
    showCancelModal.value = false;
    router.reload();
  } catch (error: any) {
    const errorMessage = error.response?.data?.error || 'Failed to cancel subscription';
    toast(errorMessage);
  } finally {
    isCancelling.value = false;
  }
};

const handleResumeSubscription = async () => {
  isResuming.value = true;
  try {
    await axios.post(route('subscriptions.resume'));

    toast('Subscription resumed successfully');
    router.reload();
  } catch (error: any) {
    const errorMessage = error.response?.data?.error || 'Failed to resume subscription';
    toast(errorMessage);
  } finally {
    isResuming.value = false;
  }
};

const handleChangePlan = (plan: any) => {
  selectedPlan.value = plan;
  showPlanSwitchModal.value = true;
};

const confirmPlanSwitch = async () => {
  if (!selectedPlan.value) return;

  isPlanSwitching.value = true;
  try {
    await axios.post(route('subscriptions.change-plan'), {
      price_id: selectedPlan.value.price_id
    });

    toast(`Successfully switched to ${selectedPlan.value.product_name}! Your billing has been updated.`);
    showPlanSwitchModal.value = false;
    router.reload();
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Failed to change plan. Please try again.';
    toast(errorMessage);
  } finally {
    isPlanSwitching.value = false;
  }
};

const loadInvoices = async () => {
  isLoadingInvoices.value = true;
  try {
    const response = await axios.get(route('subscriptions.invoices'));
    invoices.value = response.data.invoices;
  } catch (error) {
    toast('Failed to load invoices');
  } finally {
    isLoadingInvoices.value = false;
  }
};

const loadPaymentMethods = async () => {
  isLoadingPaymentMethods.value = true;
  try {
    console.log('Loading payment methods...');
    const response = await axios.get(route('subscriptions.payment-methods'));
    console.log('Payment methods response:', response.data);

    if (response.data && Array.isArray(response.data.payment_methods)) {
      paymentMethods.value = response.data.payment_methods;
      console.log('Payment methods updated:', paymentMethods.value.length, 'methods');
    } else {
      console.warn('Invalid payment methods response format:', response.data);
      paymentMethods.value = [];
    }
  } catch (error: any) {
    console.error('Error loading payment methods:', error);
    if (error.response?.status === 401) {
      toast('Authentication required. Please log in again.');
    } else if (error.response?.data?.error) {
      toast(error.response.data.error);
    } else {
      toast('Failed to load payment methods');
    }
  } finally {
    isLoadingPaymentMethods.value = false;
  }
};

const downloadInvoice = (invoiceId: string) => {
  window.open(route('subscriptions.invoices.download', invoiceId), '_blank');
};

const bulkDownloadInvoices = async () => {
  if (selectedInvoices.value.length === 0) {
    toast('Please select invoices to download');
    return;
  }

  // Filter out upcoming invoices from selection
  const downloadableInvoices = selectedInvoices.value.filter(invoiceId => {
    const invoice = invoices.value.find((inv: any) => inv.id === invoiceId);
    return invoice && !invoice.is_upcoming;
  });

  if (downloadableInvoices.length === 0) {
    toast('No downloadable invoices selected. Upcoming invoices cannot be downloaded.');
    return;
  }

  isBulkDownloading.value = true;
  try {
    const response = await axios.post(route('subscriptions.invoices.bulk-download'), {
      invoice_ids: downloadableInvoices
    });

    // Check if we should use polling (for large batches) or direct processing (for small batches)
    if (response.data.use_polling) {
      // Large batch - use background processing with polling
      toast(`Large bulk download started for ${response.data.invoice_count} invoices. Processing in background...`);

      // Start polling for download status
      pollDownloadStatus(response.data.download_id);

      selectedInvoices.value = []; // Clear selection
    } else if (response.data.download_urls && response.data.download_urls.length > 0) {
      // Small batch - process completed synchronously
      toast(`Bulk download completed! Downloading ${response.data.success_count} invoices...`);

      // Download each invoice PDF
      response.data.download_urls.forEach((invoice: any, index: number) => {
        setTimeout(() => {
          const link = document.createElement('a');
          link.href = invoice.url;
          link.download = `${invoice.number || `Invoice-${invoice.id}`}.pdf`;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }, index * 500); // Stagger downloads by 500ms
      });

      if (response.data.failure_count > 0) {
        toast(`Note: ${response.data.failure_count} invoices could not be downloaded`);
      }

      selectedInvoices.value = []; // Clear selection
    } else {
      toast('No downloadable invoices were found');
    }
  } catch (error: any) {
    console.error('Bulk download error:', error);
    if (error.response?.data?.error) {
      toast(error.response.data.error);
    } else {
      toast('Failed to download invoices');
    }
  } finally {
    isBulkDownloading.value = false;
  }
};

// Poll for download status (for large batches)
const pollDownloadStatus = async (downloadId: string) => {
  const maxAttempts = 60; // Poll for up to 5 minutes (60 * 5 seconds)
  let attempts = 0;

  const checkStatus = async () => {
    try {
      attempts++;

      const response = await axios.get(route('subscriptions.invoices.bulk-download.status', { downloadId }));
      const result = response.data;

      if (result.status === 'completed') {
        // Download completed successfully
        if (result.download_urls && result.download_urls.length > 0) {
          toast(`Large bulk download completed! Downloading ${result.success_count} invoices...`);

          // Download each invoice PDF
          result.download_urls.forEach((invoice: any, index: number) => {
            setTimeout(() => {
              const link = document.createElement('a');
              link.href = invoice.url;
              link.download = `${invoice.number || `Invoice-${invoice.id}`}.pdf`;
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }, index * 500); // Stagger downloads by 500ms
          });

          if (result.failure_count > 0) {
            toast(`Note: ${result.failure_count} invoices could not be downloaded`);
          }
        } else {
          toast('No downloadable invoices were found');
        }
        return; // Stop polling
      } else if (result.status === 'failed') {
        // Download failed
        toast(`Large bulk download failed: ${result.error || 'Unknown error'}`);
        return; // Stop polling
      } else if (result.status === 'processing') {
        // Still processing, continue polling
        if (attempts < maxAttempts) {
          setTimeout(checkStatus, 5000); // Check again in 5 seconds
        } else {
          toast('Large bulk download is taking longer than expected. Please check back later.');
        }
      }
    } catch (error: any) {
      console.error('Error checking download status:', error);
      if (attempts < maxAttempts) {
        setTimeout(checkStatus, 5000); // Retry in 5 seconds
      } else {
        toast('Unable to check download status. Please try again.');
      }
    }
  };

  // Start checking status after a short delay
  setTimeout(checkStatus, 2000);
};

const toggleInvoiceSelection = (invoiceId: string) => {
  const index = selectedInvoices.value.indexOf(invoiceId);
  if (index > -1) {
    selectedInvoices.value.splice(index, 1);
  } else {
    selectedInvoices.value.push(invoiceId);
  }
};

const selectAllInvoices = () => {
  // Only select downloadable invoices (not upcoming)
  const downloadableInvoices = invoices.value.filter((invoice: any) => !invoice.is_upcoming);
  const downloadableIds = downloadableInvoices.map((invoice: any) => invoice.id);

  if (selectedInvoices.value.length === downloadableIds.length) {
    selectedInvoices.value = [];
  } else {
    selectedInvoices.value = downloadableIds;
  }
};

const setDefaultPaymentMethod = async (paymentMethodId: string) => {
  isSettingDefault.value = true;
  try {
    await axios.post(route('subscriptions.payment-methods.default', paymentMethodId));
    toast('Default payment method updated');
    loadPaymentMethods();
  } catch (error) {
    toast('Failed to update default payment method');
  } finally {
    isSettingDefault.value = false;
  }
};

const handleDeletePaymentMethod = (paymentMethodId: string) => {
  selectedPaymentMethodId.value = paymentMethodId;
  showDeletePaymentModal.value = true;
};

const confirmDeletePaymentMethod = async () => {
  isDeletingPaymentMethod.value = true;
  try {
    await axios.delete(route('subscriptions.payment-methods.delete', selectedPaymentMethodId.value));
    toast('Payment method removed');
    showDeletePaymentModal.value = false;
    loadPaymentMethods();
  } catch (error: any) {
    // Handle validation error from backend
    if (error.response?.status === 422 && error.response?.data?.error) {
      toast(error.response.data.error);
    } else {
      toast('Failed to remove payment method');
    }
  } finally {
    isDeletingPaymentMethod.value = false;
  }
};

const handleAddPaymentMethod = async () => {
  isAddingPaymentMethod.value = true;

  try {
    // Safely load Stripe
    const Stripe = await loadStripe();

    // Always create a fresh setup intent for each new payment method
    const response = await axios.post(route('subscriptions.payment-methods.setup-intent'));
    const { client_secret } = response.data;

    // Detect dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');

    const stripe = Stripe(import.meta.env.VITE_STRIPE_KEY);
    stripeInstance.value = stripe; // Store the Stripe instance
    stripeElements.value = stripe.elements({
      clientSecret: client_secret,
      appearance: {
        theme: isDarkMode ? 'night' : 'stripe',
        variables: {
          colorPrimary: '#0570de',
          colorBackground: isDarkMode ? '#1f2937' : '#ffffff',
          colorText: isDarkMode ? '#f9fafb' : '#30313d',
          colorDanger: '#df1b41',
          fontFamily: 'system-ui, sans-serif',
          spacingUnit: '4px',
          borderRadius: '6px',
        },
        rules: {
          '.Tab': {
            border: `1px solid ${isDarkMode ? '#374151' : '#e5e7eb'}`,
            borderRadius: '6px',
            backgroundColor: isDarkMode ? '#374151' : '#ffffff',
          },
          '.Tab:hover': {
            color: '#0570de',
            backgroundColor: isDarkMode ? '#4b5563' : '#f9fafb',
          },
          '.Tab--selected': {
            borderColor: '#0570de',
            color: '#0570de',
            backgroundColor: isDarkMode ? '#1f2937' : '#ffffff',
          },
          '.Input': {
            borderRadius: '6px',
            border: `1px solid ${isDarkMode ? '#374151' : '#e5e7eb'}`,
            backgroundColor: isDarkMode ? '#374151' : '#ffffff',
            color: isDarkMode ? '#f9fafb' : '#111827',
            fontSize: '14px',
            padding: '12px',
          },
          '.Input:focus': {
            borderColor: '#0570de',
            boxShadow: '0 0 0 1px #0570de',
          },
          '.Input::placeholder': {
            color: isDarkMode ? '#9ca3af' : '#6b7280',
          },
          '.Label': {
            color: isDarkMode ? '#f9fafb' : '#111827',
            fontWeight: '500',
            fontSize: '14px',
            marginBottom: '6px',
          }
        }
      }
    });

    showAddPaymentModal.value = true;

    // Mount the card element after modal is shown
    await nextTick();
    await mountCardElement();
  } catch (error) {
    console.error('Error initializing payment form:', error);
    if (error instanceof Error) {
      toast(error.message);
    } else {
      toast('Failed to initialize payment form');
    }
  } finally {
    isAddingPaymentMethod.value = false;
  }
};

const mountCardElement = async () => {
  if (!stripeElements.value) return;

  await nextTick();

  const cardElementContainer = document.getElementById('card-element');
  if (cardElementContainer && !cardElement.value) {
    cardElement.value = stripeElements.value.create('payment', {
      layout: {
        type: 'tabs',
        defaultCollapsed: false,
      }
    });

    cardElement.value.mount('#card-element');

    // Handle real-time validation errors from the card Element
    cardElement.value.on('change', (event) => {
      const displayError = document.getElementById('card-errors');
      if (displayError) {
        if (event.error) {
          displayError.textContent = event.error.message;
        } else {
          displayError.textContent = '';
        }
      }
    });
  }
};

const addPaymentMethod = async () => {
  if (!stripeElements.value || !cardElement.value || !stripeInstance.value) {
    toast('Payment form not ready');
    return;
  }

  isAddingPaymentMethod.value = true;

  try {
    // Submit the elements to get the setup intent
    const { error } = await stripeElements.value.submit();

    if (error) {
      toast(error.message);
      return;
    }

    // Use the stored Stripe instance (same one that created the elements)
    const stripe = stripeInstance.value;

    // Use confirmSetup for payment elements (not confirmCardSetup)
    const { error: confirmError, setupIntent: confirmedSetupIntent } = await stripe.confirmSetup({
      elements: stripeElements.value,
      confirmParams: {
        return_url: window.location.origin + '/subscriptions', // Fallback URL
      },
      redirect: 'if_required' // Only redirect if required by the payment method
    });

    if (confirmError) {
      toast(confirmError.message);
      return;
    }

    // If we get here, the setup was successful
    console.log('Setup intent confirmed:', confirmedSetupIntent);

    // Extract payment method ID - it could be a string or an object
    let paymentMethodId = null;

    if (confirmedSetupIntent.payment_method) {
      // If payment_method is an object, get the id
      if (typeof confirmedSetupIntent.payment_method === 'object') {
        paymentMethodId = confirmedSetupIntent.payment_method.id;
      } else {
        // If payment_method is a string, use it directly
        paymentMethodId = confirmedSetupIntent.payment_method;
      }
    }

    console.log('Payment method ID:', paymentMethodId);
    console.log('Payment method object:', confirmedSetupIntent.payment_method);

    if (confirmedSetupIntent && paymentMethodId) {
      // Send the payment method ID to our backend to attach it to the customer
      try {
        console.log('Sending payment method to backend...');
        const response = await axios.post(route('subscriptions.payment-methods.add'), {
          payment_method_id: paymentMethodId
        });

        console.log('Backend response:', response);

        // Check if the backend response indicates success
        if (response.status === 200 && response.data.message) {
          console.log('Payment method added successfully');
          toast('Payment method added successfully!');
          showAddPaymentModal.value = false;

          // Refresh payment methods list
          await loadPaymentMethods();

          // Reset Stripe Elements
          stripeElements.value = null;
          cardElement.value = null;
          stripeInstance.value = null;
        } else {
          console.error('Unexpected response from server:', response);
          throw new Error('Unexpected response from server');
        }
      } catch (backendError: any) {
        console.error('Backend error details:', {
          error: backendError,
          response: backendError.response,
          status: backendError.response?.status,
          data: backendError.response?.data
        });

        // Handle different types of backend errors
        if (backendError.response?.status === 422) {
          toast(backendError.response.data.error || 'Invalid payment method data');
        } else if (backendError.response?.status === 500) {
          toast('Server error occurred. Please try again.');
        } else if (backendError.response?.data?.error) {
          toast(backendError.response.data.error);
        } else {
          toast('Failed to save payment method. Please try again.');
        }
      }
    } else {
      console.error('Setup intent or payment method missing:', {
        setupIntent: confirmedSetupIntent,
        paymentMethodId: paymentMethodId,
        paymentMethod: confirmedSetupIntent?.payment_method
      });
      toast('Payment method setup incomplete - no payment method ID found');
    }

  } catch (error: any) {
    console.error('Payment method error:', error);

    // More detailed error handling
    if (error.type === 'validation_error') {
      toast('Please check your card details and try again');
    } else if (error.type === 'card_error') {
      toast(error.message || 'Card error occurred');
    } else if (error.code === 'setup_intent_authentication_failure') {
      toast('Card authentication failed. Please try again');
    } else {
      toast(error.message || 'Failed to add payment method');
    }
  } finally {
    isAddingPaymentMethod.value = false;
  }
};

// Watch for modal closing to clean up Stripe Elements
watch(showAddPaymentModal, async (isOpen) => {
  if (!isOpen) {
    // Clean up when modal closes
    if (cardElement.value) {
      cardElement.value.unmount();
      cardElement.value = null;
    }
    stripeElements.value = null;
    stripeInstance.value = null; // Clear the Stripe instance
  }
});

// Lifecycle
onMounted(() => {
  // Load all content at once when page loads
  loadInvoices();
  loadPaymentMethods();
});

// Watch tab changes
const onTabChange = (tab: string) => {
  activeTab.value = tab;
  // No need to load data here since everything is loaded on mount
};
</script>

<template>
  <Head title="Subscription Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold">Subscription Management</h1>
          <p class="text-muted-foreground">Manage your subscription, billing, and payment methods</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="handleBillingPortal">
            <Settings class="mr-2 h-4 w-4" />
            Billing Portal
          </Button>
        </div>
      </div>

      <!-- Subscription Overview Card -->
      <Card v-if="subscription">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <component :is="statusIcon" :class="['h-5 w-5', statusColor]" />
                {{ subscription.product_name }}
              </CardTitle>
              <CardDescription>
                {{ formattedAmount }} / {{ subscription.interval }}
              </CardDescription>
            </div>
            <Badge :variant="subscription.status === 'active' ? 'default' : 'destructive'">
              {{ subscription.status }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <p class="text-sm text-muted-foreground">Current Period</p>
              <p class="font-medium">
                {{ new Date(subscription.current_period_start).toLocaleDateString() }} -
                {{ new Date(subscription.current_period_end).toLocaleDateString() }}
              </p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Next Billing Date</p>
              <p class="font-medium">
                {{ subscription.cancel_at_period_end ? 'Cancelled' : new Date(subscription.current_period_end).toLocaleDateString() }}
              </p>
            </div>
          </div>

          <div class="mt-4 flex gap-2">
            <Button
              v-if="subscription.cancel_at_period_end"
              @click="handleResumeSubscription"
              :disabled="isLoading"
            >
              <RefreshCw class="mr-2 h-4 w-4" />
              Resume Subscription
            </Button>
            <Button
              v-else
              variant="destructive"
              @click="handleCancelSubscription"
              :disabled="isLoading"
            >
              <XCircle class="mr-2 h-4 w-4" />
              Cancel Subscription
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- No Subscription Card -->
      <Card v-else>
        <CardHeader>
          <CardTitle>No Active Subscription</CardTitle>
          <CardDescription>You don't have an active subscription</CardDescription>
        </CardHeader>
        <CardContent>
          <Button @click="router.visit('/#pricing')">
            <Plus class="mr-2 h-4 w-4" />
            Choose a Plan
          </Button>
        </CardContent>
      </Card>

      <!-- Tabs for different sections -->
      <Tabs default-value="invoices" class="flex-1" @update:value="onTabChange">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="invoices">Invoices</TabsTrigger>
          <TabsTrigger value="payment-methods">Payment Methods</TabsTrigger>
        </TabsList>

        <!-- Overview Tab -->
        <TabsContent value="overview" class="space-y-4">
          <!-- Available Plans -->
          <Card v-if="availablePlans.length > 0">
            <CardHeader>
              <CardTitle>Available Plans</CardTitle>
              <CardDescription>Change your subscription plan</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="plan in availablePlans"
                  :key="plan.price_id"
                  class="border rounded-lg p-4"
                  :class="plan.is_current ? 'border-primary bg-primary/5' : 'border-border'"
                >
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold">{{ plan.product_name }}</h3>
                    <Badge v-if="plan.is_current" variant="default">Current</Badge>
                  </div>
                  <p class="text-2xl font-bold">
                    {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: plan.currency.toUpperCase() }).format(plan.amount / 100) }}
                    <span class="text-sm font-normal text-muted-foreground">/ {{ plan.interval }}</span>
                  </p>
                  <Button
                    v-if="!plan.is_current"
                    class="w-full mt-4"
                    variant="outline"
                    @click="handleChangePlan(plan)"
                    :disabled="isLoading"
                  >
                    Switch to this plan
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Invoices Tab -->
        <TabsContent value="invoices" class="space-y-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Billing History</CardTitle>
                  <CardDescription>Download and manage your invoices</CardDescription>
                </div>
                <div class="flex gap-2">
                  <Button
                    v-if="selectedInvoices.length > 0"
                    variant="outline"
                    @click="bulkDownloadInvoices"
                    :disabled="isBulkDownloading"
                  >
                    <Loader2 v-if="isBulkDownloading" class="mr-2 h-4 w-4 animate-spin" />
                    <Download v-else class="mr-2 h-4 w-4" />
                    {{ isBulkDownloading ? 'Downloading...' : `Download Selected (${selectedInvoices.length})` }}
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="invoices.length === 0" class="text-center py-8">
                <p class="text-muted-foreground">No invoices found</p>
              </div>
              <div v-else class="space-y-4">
                <!-- Select All Checkbox -->
                <div class="flex items-center space-x-2 border-b pb-2">
                  <input
                    type="checkbox"
                    :checked="selectedInvoices.length === invoices.filter((inv) => !inv.is_upcoming).length && invoices.filter((inv) => !inv.is_upcoming).length > 0"
                    @change="selectAllInvoices"
                    class="rounded border-gray-300"
                  />
                  <label class="text-sm font-medium">
                    Select All Downloadable ({{ invoices.filter((inv) => !inv.is_upcoming).length }} invoices)
                  </label>
                </div>

                <!-- Invoice List -->
                <div class="space-y-2">
                  <div
                    v-for="invoice in invoices"
                    :key="invoice.id"
                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50"
                  >
                    <div class="flex items-center space-x-3">
                      <input
                        type="checkbox"
                        :checked="selectedInvoices.includes(invoice.id)"
                        @change="toggleInvoiceSelection(invoice.id)"
                        :disabled="invoice.is_upcoming"
                        class="rounded border-gray-300"
                        :title="invoice.is_upcoming ? 'Upcoming invoices cannot be downloaded' : 'Select for bulk download'"
                      />
                      <div>
                        <p class="font-medium">
                          {{ invoice.number || `Invoice ${invoice.id.slice(-8)}` }}
                          <Badge v-if="invoice.is_upcoming" variant="outline" class="ml-2 text-xs">
                            Upcoming
                          </Badge>
                        </p>
                        <p class="text-sm text-muted-foreground">
                          {{ invoice.date }}
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center space-x-4">
                      <div class="text-right">
                        <p class="font-medium">
                          {{ new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: invoice.currency.toUpperCase()
                          }).format(invoice.amount_paid / 100) }}
                        </p>
                        <Badge
                          :variant="invoice.status === 'paid' ? 'default' : 'destructive'"
                          class="text-xs"
                        >
                          {{ invoice.status }}
                        </Badge>
                      </div>
                      <Button
                        variant="outline"
                        size="sm"
                        @click="downloadInvoice(invoice.id)"
                        :disabled="invoice.is_upcoming"
                        :title="invoice.is_upcoming ? 'Upcoming invoices cannot be downloaded' : 'Download invoice'"
                      >
                        <Download class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Payment Methods Tab -->
        <TabsContent value="payment-methods" class="space-y-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Payment Methods</CardTitle>
                  <CardDescription>Manage your saved payment methods</CardDescription>
                </div>
                <Button
                  variant="outline"
                  @click="handleAddPaymentMethod"
                  :disabled="isAddingPaymentMethod"
                >
                  <Loader2 v-if="isAddingPaymentMethod" class="mr-2 h-4 w-4 animate-spin" />
                  <Plus v-else class="mr-2 h-4 w-4" />
                  {{ isAddingPaymentMethod ? 'Initializing...' : 'Add Payment Method' }}
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <!-- Loading State -->
              <div v-if="isLoadingPaymentMethods" class="text-center py-8">
                <Loader2 class="mx-auto h-8 w-8 animate-spin text-muted-foreground mb-4" />
                <p class="text-muted-foreground">Loading payment methods...</p>
              </div>

              <!-- No Payment Methods -->
              <div v-else-if="paymentMethods.length === 0" class="text-center py-8">
                <CreditCard class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <p class="text-muted-foreground">No payment methods found</p>
                <Button
                  variant="outline"
                  class="mt-4"
                  @click="handleAddPaymentMethod"
                  :disabled="isAddingPaymentMethod"
                >
                  <Loader2 v-if="isAddingPaymentMethod" class="mr-2 h-4 w-4 animate-spin" />
                  <Plus v-else class="mr-2 h-4 w-4" />
                  {{ isAddingPaymentMethod ? 'Initializing...' : 'Add Your First Payment Method' }}
                </Button>
              </div>

              <!-- Payment Methods List -->
              <div v-else class="space-y-4">
                <div
                  v-for="method in paymentMethods"
                  :key="method.id"
                  class="flex items-center justify-between p-4 border rounded-lg"
                >
                  <div class="flex items-center space-x-3">
                    <CreditCard class="h-8 w-8 text-muted-foreground" />
                    <div>
                      <p class="font-medium">
                        {{ method.card.brand.toUpperCase() }} •••• {{ method.card.last4 }}
                      </p>
                      <p class="text-sm text-muted-foreground">
                        Expires {{ method.card.exp_month }}/{{ method.card.exp_year }}
                      </p>
                    </div>
                    <Badge v-if="method.is_default" variant="default">Default</Badge>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Button
                      v-if="!method.is_default"
                      variant="outline"
                      size="sm"
                      @click="setDefaultPaymentMethod(method.id)"
                      :disabled="isSettingDefault"
                    >
                      <Loader2 v-if="isSettingDefault" class="mr-2 h-4 w-4 animate-spin" />
                      {{ isSettingDefault ? 'Setting...' : 'Set as Default' }}
                    </Button>
                    <div class="relative group">
                      <Button
                        variant="outline"
                        size="sm"
                        :class="[
                          canDeletePaymentMethod(method)
                            ? 'text-destructive hover:text-destructive'
                            : 'text-muted-foreground cursor-not-allowed'
                        ]"
                        @click="canDeletePaymentMethod(method) ? handleDeletePaymentMethod(method.id) : null"
                        :disabled="isDeletingPaymentMethod || !canDeletePaymentMethod(method)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>

                      <!-- Tooltip for disabled delete button -->
                      <div
                        v-if="!canDeletePaymentMethod(method)"
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 text-xs text-white bg-gray-900 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10"
                      >
                        Cannot delete default payment method with active subscription
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>

    <!-- Cancel Subscription Modal -->
    <Dialog v-model:open="showCancelModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Cancel Subscription</DialogTitle>
          <DialogDescription>
            Choose when you'd like your subscription to be cancelled.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-3">
            <Label class="text-sm font-medium">Cancellation Options</Label>
            <div class="space-y-2">
              <div class="flex items-center space-x-2">
                <input
                  type="radio"
                  id="at_period_end"
                  v-model="cancelOption"
                  value="at_period_end"
                  class="rounded border-gray-300"
                />
                <Label for="at_period_end" class="text-sm">
                  Cancel at period end (Recommended)
                </Label>
              </div>
              <p class="text-xs text-muted-foreground ml-6">
                Your subscription will remain active until {{ props.subscription?.current_period_end ? new Date(props.subscription.current_period_end).toLocaleDateString() : 'the end of your billing period' }}
              </p>

              <div class="flex items-center space-x-2">
                <input
                  type="radio"
                  id="immediately"
                  v-model="cancelOption"
                  value="immediately"
                  class="rounded border-gray-300"
                />
                <Label for="immediately" class="text-sm text-destructive">
                  Cancel immediately
                </Label>
              </div>
              <p class="text-xs text-muted-foreground ml-6">
                Your subscription will be cancelled right away and you'll lose access immediately
              </p>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showCancelModal = false">
            Keep Subscription
          </Button>
          <Button
            variant="destructive"
            @click="confirmCancelSubscription"
            :disabled="isLoading"
          >
            <XCircle class="mr-2 h-4 w-4" />
            {{ cancelOption === 'immediately' ? 'Cancel Now' : 'Cancel at Period End' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Delete Payment Method Modal -->
    <Dialog v-model:open="showDeletePaymentModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Remove Payment Method</DialogTitle>
          <DialogDescription>
            Are you sure you want to remove this payment method? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showDeletePaymentModal = false">
            Cancel
          </Button>
          <Button
            variant="destructive"
            @click="confirmDeletePaymentMethod"
            :disabled="isDeletingPaymentMethod"
          >
            <Loader2 v-if="isDeletingPaymentMethod" class="mr-2 h-4 w-4 animate-spin" />
            <Trash2 v-else class="mr-2 h-4 w-4" />
            {{ isDeletingPaymentMethod ? 'Removing...' : 'Remove Payment Method' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Plan Switch Modal -->
    <Dialog v-model:open="showPlanSwitchModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Switch Subscription Plan</DialogTitle>
          <DialogDescription>
            Confirm your plan change. You'll be charged or credited the prorated amount.
          </DialogDescription>
        </DialogHeader>
        <div v-if="selectedPlan" class="space-y-4">
          <div class="rounded-lg border p-4 bg-muted/50">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium">{{ selectedPlan.product_name }}</span>
              <Badge variant="default">New Plan</Badge>
            </div>
            <p class="text-2xl font-bold">
              {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: selectedPlan.currency.toUpperCase() }).format(selectedPlan.amount / 100) }}
              <span class="text-sm font-normal text-muted-foreground">/ {{ selectedPlan.interval }}</span>
            </p>
          </div>

          <div class="rounded-lg border p-4 bg-blue-50 dark:bg-blue-950/20">
            <div class="flex items-center space-x-2">
              <AlertTriangle class="h-4 w-4 text-blue-600" />
              <span class="text-sm font-medium text-blue-600">Prorated Billing</span>
            </div>
            <p class="text-xs text-blue-600 mt-1">
              You'll be charged or credited the difference for your current billing period. The change takes effect immediately.
            </p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showPlanSwitchModal = false" :disabled="isPlanSwitching">
            Cancel
          </Button>
          <Button
            @click="confirmPlanSwitch"
            :disabled="isPlanSwitching"
          >
            <Loader2 v-if="isPlanSwitching" class="mr-2 h-4 w-4 animate-spin" />
            <RefreshCw v-else class="mr-2 h-4 w-4" />
            {{ isPlanSwitching ? 'Switching Plan...' : 'Switch Plan' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Add Payment Method Modal -->
    <Dialog v-model:open="showAddPaymentModal">
      <DialogContent class="sm:max-w-md max-h-[90vh] overflow-y-auto">
        <DialogHeader class="pb-4">
          <DialogTitle class="text-lg">Add Payment Method</DialogTitle>
          <DialogDescription class="text-sm">
            Add a new credit or debit card to your account.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3 py-2">
          <!-- Security Notice - Compact -->
          <div class="rounded-lg border p-3 bg-muted/50">
            <div class="flex items-center space-x-2">
              <CreditCard class="h-4 w-4 text-muted-foreground" />
              <span class="text-xs font-medium">Secure Payment Processing</span>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              Processed securely by Stripe. Never stored on our servers.
            </p>
          </div>

          <!-- Stripe Elements Card Input -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Card Information</Label>
            <div
              id="card-element"
              class="p-3 border rounded-md bg-background"
              style="min-height: 40px;"
            ></div>
            <div id="card-errors" class="text-xs text-destructive min-h-[16px]"></div>
          </div>
        </div>

        <DialogFooter class="pt-4 gap-2">
          <Button
            variant="outline"
            size="sm"
            @click="showAddPaymentModal = false"
            :disabled="isAddingPaymentMethod"
          >
            Cancel
          </Button>
          <Button
            size="sm"
            @click="addPaymentMethod"
            :disabled="isAddingPaymentMethod"
          >
            <Loader2 v-if="isAddingPaymentMethod" class="mr-2 h-3 w-3 animate-spin" />
            <Plus v-else class="mr-2 h-3 w-3" />
            {{ isAddingPaymentMethod ? 'Adding...' : 'Add Payment Method' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
