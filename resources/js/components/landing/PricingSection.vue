<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { useSubscriptionState } from '@/composables/useSubscriptionState';
import { Loader2 } from 'lucide-vue-next';

const {
  isAuthenticated,
  subscriptionState,
  subscriptionMessage,
  ctaText,
  shouldShowPricing,
  needsSubscription,
  canUpgrade
} = useSubscriptionState();

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

const billingPeriod = ref('monthly');
const isLoading = ref(true);
const products = ref([]);
const errorMessage = ref('');
const loadingProductId = ref(null); // Track which product is loading

// Function to scroll to the Talk to Us section
const scrollToTalkToUs = () => {
  const talkToUsSection = document.getElementById('talk-to-us');
  if (talkToUsSection) {
    // Use GSAP for smooth scrolling
    gsap.to(window, {
      duration: 1.2,
      scrollTo: {
        y: talkToUsSection,
        offsetY: 100,
        autoKill: false
      },
      ease: 'power3.inOut'
    });
  }
};

// Handle enterprise contact button click
const handleEnterpriseContact = () => {
  toast('Contact our sales team', {
    description: 'Get in touch with our enterprise sales team for a custom solution.',
    action: {
      label: 'Contact now',
      onClick: scrollToTalkToUs
    },
    duration: 5000
  });

  // Also scroll to the section
  setTimeout(scrollToTalkToUs, 300);
};

// Fetch products from Stripe
const fetchProducts = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/stripe/products');

    if (response.data.success) {
      products.value = response.data.products;
    } else {
      errorMessage.value = response.data.message || 'No pricing plans available.';
    }
  } catch (error) {
    console.error('Error fetching pricing plans:', error);
    errorMessage.value = 'No pricing plans available. Please contact our sales team for more information.';
    toast('Error loading pricing plans');
  } finally {
    isLoading.value = false;
  }
};

// Calculate the average discount percentage for yearly plans
const yearlyDiscountPercentage = computed(() => {
  if (!products.value.length) return null;

  // Get all yearly products with a discount percentage
  const yearlyProducts = products.value.filter(product =>
    product.interval === 'year' && product.discount_percentage
  );

  if (yearlyProducts.length === 0) return 20; // Fallback to 20% if no dynamic discounts

  // Calculate the average discount percentage
  const totalDiscount = yearlyProducts.reduce((sum, product) => sum + product.discount_percentage, 0);
  return Math.round(totalDiscount / yearlyProducts.length);
});

// Filter products by billing period
const filteredProducts = computed(() => {
  if (!products.value.length) return [];

  return products.value.filter(product => {
    // For monthly billing period, show products with monthly interval
    if (billingPeriod.value === 'monthly') {
      return !product.interval || product.interval === 'month';
    }

    // For yearly billing period, show products with yearly interval
    if (billingPeriod.value === 'yearly') {
      return product.interval === 'year';
    }

    return true;
  });
});

// No fallback features - we'll show a message if no products are found

onMounted(() => {
  // Fetch products from Stripe
  fetchProducts();

  // Animate the pricing cards
  gsap.utils.toArray('.pricing-card').forEach((card: any, i) => {
    gsap.from(card, {
      y: 40,
      opacity: 0,
      duration: 0.7,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '#pricing',
        start: 'top 80%',
        toggleActions: 'play none none none'
      },
      delay: i * 0.2 // Stagger the animations
    });
  });

  // Note: Auto-scroll is now handled by the Landing page after preloader finishes
});



// Handle subscription checkout
const handleSubscriptionAction = async (product: any) => {
  if (!isAuthenticated.value) {
    // Redirect to register
    router.visit(route('register'));
    return;
  }

  if (product.name.toLowerCase().includes('enterprise')) {
    handleEnterpriseContact();
    return;
  }

  // Get the price_id directly from the product (since each product already has the correct price for its interval)
  const priceId = product.price_id;

  if (!priceId) {
    toast('Price not found for this product');
    return;
  }

  // Set loading state for this specific product
  loadingProductId.value = product.id;

  try {
    // Create checkout session
    const response = await axios.post(route('subscription.checkout'), {
      price_id: priceId
    });

    if (response.data.checkout_url) {
      // Keep loading state while redirecting to Stripe
      toast('Redirecting to checkout...', {
        description: 'Please wait while we redirect you to Stripe.'
      });

      // Small delay to show the loading state before redirect
      setTimeout(() => {
        window.location.href = response.data.checkout_url;
      }, 500);
    } else {
      toast('Failed to create checkout session');
      loadingProductId.value = null;
    }
  } catch (error) {
    console.error('Checkout error:', error);
    toast('Failed to start checkout process');
    loadingProductId.value = null;
  }
};

// Check if a product is loading
const isProductLoading = (product: any) => {
  return loadingProductId.value === product.id;
};

// Get button text for product
const getButtonText = (product: any) => {
  if (isProductLoading(product)) {
    return 'Processing...';
  }

  if (product.name.toLowerCase().includes('enterprise')) {
    return 'Contact Sales';
  }

  if (!isAuthenticated.value) {
    return 'Get Started';
  }

  return ctaText.value;
};

// Get button variant for product
const getButtonVariant = (product: any) => {
  return product.is_popular ? 'default' : 'outline';
};

// Cleanup loading state on component unmount
onUnmounted(() => {
  loadingProductId.value = null;
});

</script>

<template>
  <section id="pricing" class="animate-section relative py-20 md:py-32">
    <div class="container mx-auto px-4 md:px-6">
      <div class="mb-16 text-center">
        <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Simple, Transparent Pricing</h2>
        <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
          {{ subscriptionMessage }}
        </p>

        <!-- Billing toggle using Tabs -->
        <div class="mt-8 flex justify-center">
          <Tabs v-model="billingPeriod" class="w-full">
            <TabsList class="mx-auto w-full max-w-xs grid grid-cols-2">
              <TabsTrigger value="monthly">Monthly</TabsTrigger>
              <TabsTrigger value="yearly" class="relative">
                Yearly
                <span v-if="yearlyDiscountPercentage" class="absolute -right-2 -top-2 rounded-full bg-green-500/90 px-2 py-0.5 text-xs font-medium text-white">-{{ yearlyDiscountPercentage }}%</span>
              </TabsTrigger>
            </TabsList>

            <!-- Loading state -->
            <div v-if="isLoading" class="mt-6 flex flex-col items-center justify-center py-12">
              <div class="h-12 w-12 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
              <p class="mt-4 text-muted-foreground">Loading pricing plans...</p>
            </div>

            <!-- Error message -->
            <div v-else-if="errorMessage" class="mt-6">
              <div class="mx-auto max-w-2xl rounded-lg border border-border/50 bg-background p-8 text-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-muted-foreground"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <h3 class="mb-2 text-2xl font-medium">No Pricing Plans Available</h3>
                <p class="mb-6 text-muted-foreground">
                  We're currently updating our pricing plans. Please contact our sales team for more information.
                </p>
                <Button as="a" href="mailto:sales@rilltech.com" class="mx-auto">Contact Sales</Button>
              </div>
            </div>


            <!-- Dynamic pricing cards -->
            <div v-if="!isLoading && !errorMessage">
              <!-- Monthly pricing -->
              <TabsContent value="monthly" class="mt-6">
                <div v-if="filteredProducts.length" class="grid gap-6 md:grid-cols-3">
                  <div v-for="product in filteredProducts" :key="product.id" class="pricing-card">
                    <Card :class="[
                      'h-full transition-all duration-300 hover:shadow-lg',
                      product.is_popular ? 'relative border-primary/50 shadow-lg' : 'border-border/50 hover:border-primary/50'
                    ]">
                      <div v-if="product.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-1 text-xs font-medium text-primary-foreground">
                        Most Popular
                      </div>
                      <CardHeader>
                        <CardTitle>{{ product.name }}</CardTitle>
                        <CardDescription>{{ product.description }}</CardDescription>
                        <div class="mt-4">
                          <span v-if="product.amount === 0" class="text-4xl font-bold">Free</span>
                          <template v-else>
                            <span class="text-4xl font-bold">{{ product.currency === 'USD' ? '$' : product.currency }} {{ product.amount }}</span>
                            <span class="text-muted-foreground">/{{ product.interval || 'month' }}</span>
                          </template>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <ul class="space-y-2">
                          <li v-for="feature in product.features" :key="typeof feature === 'object' ? feature.name : feature" class="flex items-center gap-2">
                            <svg v-if="typeof feature === 'object' ? feature.included : true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-green-500"><polyline points="20 6 9 17 4 12"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x text-red-500"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span :class="{ 'text-muted-foreground': typeof feature === 'object' && !feature.included }">
                              {{ typeof feature === 'object' ? feature.name : feature }}
                            </span>
                          </li>
                        </ul>
                      </CardContent>
                      <CardFooter>
                        <Button
                          :variant="getButtonVariant(product)"
                          class="w-full"
                          :disabled="isProductLoading(product)"
                          @click="() => handleSubscriptionAction(product)"
                        >
                          <Loader2 v-if="isProductLoading(product)" class="mr-2 h-4 w-4 animate-spin" />
                          {{ getButtonText(product) }}
                        </Button>
                      </CardFooter>
                    </Card>
                  </div>
                </div>
              </TabsContent>

              <!-- Yearly pricing -->
              <TabsContent value="yearly" class="mt-6">
                <div v-if="filteredProducts.length" class="grid gap-6 md:grid-cols-3">
                  <div v-for="product in filteredProducts" :key="product.id" class="pricing-card">
                    <Card :class="[
                      'h-full transition-all duration-300 hover:shadow-lg',
                      product.is_popular ? 'relative border-primary/50 shadow-lg' : 'border-border/50 hover:border-primary/50'
                    ]">
                      <div v-if="product.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-1 text-xs font-medium text-primary-foreground">
                        Most Popular
                      </div>
                      <CardHeader>
                        <CardTitle>{{ product.name }}</CardTitle>
                        <CardDescription>{{ product.description }}</CardDescription>
                        <div class="mt-4">
                          <span v-if="product.amount === 0" class="text-4xl font-bold">Free</span>
                          <template v-else>
                            <span class="text-4xl font-bold">{{ product.currency === 'USD' ? '$' : product.currency }} {{ product.amount }}</span>
                            <span class="text-muted-foreground">/year</span>
                          </template>
                        </div>
                        <div v-if="product.amount > 0" class="mt-1 text-sm text-green-500">
                          <template v-if="product.discount_percentage">
                            <span class="font-medium">Save {{ product.discount_percentage }}%</span> compared to monthly
                          </template>
                          <template v-else-if="product.monthly_equivalent">
                            <span class="font-medium">{{ product.currency === 'USD' ? '$' : product.currency }}{{ product.monthly_equivalent.toFixed(2) }}/month</span> equivalent
                          </template>
                          <template v-else>
                            <span class="font-medium">Save {{ product.currency === 'USD' ? '$' : product.currency }}{{ (product.amount * 12 * 0.2).toFixed(0) }}/year</span> compared to monthly
                          </template>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <ul class="space-y-2">
                          <li v-for="feature in product.features" :key="typeof feature === 'object' ? feature.name : feature" class="flex items-center gap-2">
                            <svg v-if="typeof feature === 'object' ? feature.included : true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-green-500"><polyline points="20 6 9 17 4 12"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x text-red-500"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span :class="{ 'text-muted-foreground': typeof feature === 'object' && !feature.included }">
                              {{ typeof feature === 'object' ? feature.name : feature }}
                            </span>
                          </li>
                        </ul>
                      </CardContent>
                      <CardFooter>
                        <Button
                          :variant="getButtonVariant(product)"
                          class="w-full"
                          :disabled="isProductLoading(product)"
                          @click="() => handleSubscriptionAction(product)"
                        >
                          <Loader2 v-if="isProductLoading(product)" class="mr-2 h-4 w-4 animate-spin" />
                          {{ getButtonText(product) }}
                        </Button>
                      </CardFooter>
                    </Card>
                  </div>
                </div>

                <!-- No yearly plans found -->
                <div v-else class="mx-auto max-w-2xl rounded-lg border border-border/50 bg-background p-6 text-center shadow-sm">
                  <p class="text-muted-foreground">
                    No yearly plans are currently available. Please check our monthly plans or contact sales for custom pricing.
                  </p>
                </div>
              </TabsContent>
            </div>
          </Tabs>
        </div>
      </div>

      <div class="mt-12 text-center">
        <Link href="/pricing" class="text-primary hover:underline">
          View full pricing details →
        </Link>
      </div>
    </div>
  </section>
</template>
