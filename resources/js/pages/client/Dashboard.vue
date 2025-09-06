<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import SubscriptionSuccessModal from '@/components/modals/SubscriptionSuccessModal.vue';

interface Props {
  subscription?: {
    id: number;
    status: string;
    current_period_end: string;
    cancel_at_period_end: boolean;
  };
}

const props = defineProps<Props>();

const showSuccessModal = ref(false);
const sessionId = ref('');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Check for subscription success from URL params
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const subscriptionSuccess = urlParams.get('subscription_success');
  const sessionIdParam = urlParams.get('session_id');

  if (subscriptionSuccess === 'true' && sessionIdParam) {
    showSuccessModal.value = true;
    sessionId.value = sessionIdParam;

    // Clean up URL without page reload
    const url = new URL(window.location.href);
    url.searchParams.delete('subscription_success');
    url.searchParams.delete('session_id');
    window.history.replaceState({}, '', url.toString());
  }
});

const handleSuccessModalClose = () => {
  showSuccessModal.value = false;
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'active':
      return { variant: 'default' as const, text: 'Active' };
    case 'canceled':
      return { variant: 'secondary' as const, text: 'Cancelled' };
    case 'past_due':
      return { variant: 'destructive' as const, text: 'Past Due' };
    case 'trialing':
      return { variant: 'outline' as const, text: 'Trial' };
    default:
      return { variant: 'secondary' as const, text: status };
  }
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

// import { useEcho , useEchoModel } from "@laravel/echo-vue";
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Welcome Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Welcome to your Dashboard</h1>
                    <p class="text-muted-foreground">Manage your AI assistant and subscription</p>
                </div>
                <Button @click="router.visit(route('subscriptions.index'))">
                    Manage Subscription
                </Button>
            </div>

            <!-- Subscription Status Card -->
            <Card v-if="props.subscription" class="border-primary/20 bg-primary/5">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="16 12 12 8 8 12"/>
                                <line x1="12" x2="12" y1="16" y2="8"/>
                            </svg>
                            Subscription Status
                        </CardTitle>
                        <Badge :variant="getStatusBadge(props.subscription.status).variant">
                            {{ getStatusBadge(props.subscription.status).text }}
                        </Badge>
                    </div>
                    <CardDescription>
                        Your subscription is {{ props.subscription.status }}
                        <span v-if="props.subscription.cancel_at_period_end" class="text-destructive">
                            and will cancel on {{ formatDate(props.subscription.current_period_end) }}
                        </span>
                        <span v-else>
                            and renews on {{ formatDate(props.subscription.current_period_end) }}
                        </span>
                    </CardDescription>
                </CardHeader>
            </Card>

            <!-- Main Dashboard Grid -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- AI Assistant Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                            AI Assistant
                        </CardTitle>
                        <CardDescription>Build and manage your AI helpers</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-8">
                            <p class="text-muted-foreground mb-4">Start building your first AI assistant</p>
                            <Button variant="outline">Create Assistant</Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Analytics Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <path d="M3 3v18h18"/>
                                <path d="M18 17V9"/>
                                <path d="M13 17V5"/>
                                <path d="M8 17v-3"/>
                            </svg>
                            Analytics
                        </CardTitle>
                        <CardDescription>Track your AI assistant performance</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-8">
                            <p class="text-muted-foreground mb-4">View detailed analytics</p>
                            <Button variant="outline">View Analytics</Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Settings Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Settings
                        </CardTitle>
                        <CardDescription>Manage your account settings</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-8">
                            <p class="text-muted-foreground mb-4">Configure your preferences</p>
                            <Button variant="outline" @click="router.visit(route('profile.edit'))">
                                Account Settings
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content Area -->
            <div class="relative min-h-[50vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Your AI Workspace</h3>
                        <p class="text-muted-foreground mb-4">This is where you'll build and manage your AI assistants</p>
                        <Button>Get Started</Button>
                    </div>
                </div>
                <PlaceholderPattern />
            </div>
        </div>

        <!-- Subscription Success Modal -->
        <SubscriptionSuccessModal
          v-model:open="showSuccessModal"
          :session-id="sessionId"
          @close="handleSuccessModalClose"
        />
    </AppLayout>
</template>
