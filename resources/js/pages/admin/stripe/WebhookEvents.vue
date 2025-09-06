<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { AlertCircle, ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
  events: Array,
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
    title: 'Webhooks',
    href: '/admin/stripe/webhooks',
  },
  {
    title: 'Events',
    href: '/admin/stripe/webhooks/events',
  },
];

// Format date
const formatDate = (timestamp) => {
  if (!timestamp) return 'N/A';
  return new Date(timestamp).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

// Open/closed state for event details
const openEvents = ref({});

// Toggle event details
const toggleEvent = (eventId) => {
  openEvents.value = {
    ...openEvents.value,
    [eventId]: !openEvents.value[eventId],
  };
};

// Get event type badge color
const getEventTypeColor = (type) => {
  if (type.startsWith('customer.subscription')) {
    return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
  } else if (type.startsWith('customer')) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
  } else if (type.startsWith('invoice')) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
  } else if (type.startsWith('charge')) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
  } else if (type.startsWith('payment_intent')) {
    return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400';
  } else {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
  }
};
</script>

<template>
  <Head title="Webhook Events" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Webhook Events</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('admin.stripe.webhooks')">
              Back to Webhooks
            </Link>
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

      <!-- Events table -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Events</CardTitle>
          <CardDescription>
            Recent events from Stripe. Click on an event to see its details.
          </CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Event Type</TableHead>
                <TableHead>Created</TableHead>
                <TableHead>Live Mode</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-for="event in events" :key="event.id">
                <TableRow class="cursor-pointer" @click="toggleEvent(event.id)">
                  <TableCell>
                    <span :class="[
                      'rounded-full px-2 py-1 text-xs font-medium',
                      getEventTypeColor(event.type)
                    ]">
                      {{ event.type }}
                    </span>
                  </TableCell>
                  <TableCell>{{ formatDate(event.created) }}</TableCell>
                  <TableCell>
                    <span :class="[
                      'rounded-full px-2 py-1 text-xs font-medium',
                      event.livemode ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
                    ]">
                      {{ event.livemode ? 'Live' : 'Test' }}
                    </span>
                  </TableCell>
                  <TableCell class="text-right">
                    <Button variant="ghost" size="sm" @click.stop="toggleEvent(event.id)">
                      <ChevronDown v-if="openEvents[event.id]" class="h-4 w-4" />
                      <ChevronRight v-else class="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
                <TableRow v-if="openEvents[event.id]">
                  <TableCell colspan="4" class="p-0">
                    <div class="border-t bg-muted/50 p-4">
                      <h3 class="mb-2 text-sm font-medium">Event Details</h3>
                      <div class="rounded-md bg-muted p-4">
                        <pre class="max-h-96 overflow-auto text-xs">{{ JSON.stringify(event.data, null, 2) }}</pre>
                      </div>
                      <div v-if="event.request" class="mt-4">
                        <h3 class="mb-2 text-sm font-medium">Request Details</h3>
                        <div class="rounded-md bg-muted p-4">
                          <pre class="max-h-40 overflow-auto text-xs">{{ JSON.stringify(event.request, null, 2) }}</pre>
                        </div>
                      </div>
                    </div>
                  </TableCell>
                </TableRow>
              </template>
              <TableRow v-if="events && events.length === 0">
                <TableCell colspan="4" class="h-24 text-center">
                  No events found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
        <CardFooter class="border-t p-4">
          <p class="text-sm text-muted-foreground">
            Showing the 20 most recent events from Stripe.
          </p>
        </CardFooter>
      </Card>
    </div>
  </AdminLayout>
</template>
