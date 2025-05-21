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
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, Plus, Webhook, Copy, Check, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  webhooks: Array,
  defaultEvents: Array,
  webhookUrl: String,
  webhookSecret: String,
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
];

// Create webhook form
const createWebhookForm = useForm({
  url: props.webhookUrl || '',
  events: props.defaultEvents || [],
  description: 'Created from admin dashboard',
});

// Update webhook form
const updateWebhookForm = useForm({
  events: [],
  description: '',
});

// Selected webhook for update
const selectedWebhook = ref(null);

// Copy to clipboard
const copied = ref(false);
const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 2000);
};

// Submit create webhook form
const submitCreateWebhook = () => {
  createWebhookForm.post(route('admin.stripe.webhooks.store'), {
    onSuccess: () => {
      toast.success('Webhook endpoint created successfully');
      createWebhookForm.reset();
      document.getElementById('close-dialog')?.click();
    },
    onError: (errors) => {
      toast.error('Failed to create webhook endpoint');
      console.error(errors);
    },
  });
};

// Prepare update webhook form
const prepareUpdateWebhook = (webhook) => {
  selectedWebhook.value = webhook;
  updateWebhookForm.events = webhook.events || [];
  updateWebhookForm.description = webhook.description || '';
};

// Submit update webhook form
const submitUpdateWebhook = () => {
  if (!selectedWebhook.value) return;
  
  updateWebhookForm.put(route('admin.stripe.webhooks.update', selectedWebhook.value.id), {
    onSuccess: () => {
      toast.success('Webhook endpoint updated successfully');
      updateWebhookForm.reset();
      selectedWebhook.value = null;
      document.getElementById('close-update-dialog')?.click();
    },
    onError: (errors) => {
      toast.error('Failed to update webhook endpoint');
      console.error(errors);
    },
  });
};

// Delete webhook
const deleteWebhook = (id) => {
  if (confirm('Are you sure you want to delete this webhook endpoint?')) {
    router.delete(route('admin.stripe.webhooks.destroy', id), {
      onSuccess: () => {
        toast.success('Webhook endpoint deleted successfully');
      },
      onError: (errors) => {
        toast.error('Failed to delete webhook endpoint');
        console.error(errors);
      },
    });
  }
};

// Toggle event selection
const toggleEvent = (event, form) => {
  const index = form.events.indexOf(event);
  if (index === -1) {
    form.events.push(event);
  } else {
    form.events.splice(index, 1);
  }
};

// Select all events
const selectAllEvents = (form) => {
  form.events = [...props.defaultEvents];
};

// Deselect all events
const deselectAllEvents = (form) => {
  form.events = [];
};
</script>

<template>
  <Head title="Stripe Webhooks" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="container py-6">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Stripe Webhooks</h1>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('admin.stripe.webhooks.events')">
              View Recent Events
            </Link>
          </Button>
          <Dialog>
            <DialogTrigger asChild>
              <Button class="flex items-center gap-2">
                <Plus class="h-4 w-4" />
                Create Webhook
              </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-[600px]">
              <DialogHeader>
                <DialogTitle>Create Webhook Endpoint</DialogTitle>
                <DialogDescription>
                  Create a new webhook endpoint to receive events from Stripe.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="submitCreateWebhook">
                <div class="grid gap-4 py-4">
                  <div>
                    <label for="url" class="block text-sm font-medium mb-2">Webhook URL</label>
                    <Input id="url" v-model="createWebhookForm.url" placeholder="https://example.com/webhook" required />
                    <p v-if="createWebhookForm.errors.url" class="mt-1 text-sm text-destructive">
                      {{ createWebhookForm.errors.url }}
                    </p>
                  </div>
                  <div>
                    <label for="description" class="block text-sm font-medium mb-2">Description</label>
                    <Textarea id="description" v-model="createWebhookForm.description" placeholder="Description for this webhook endpoint" />
                    <p v-if="createWebhookForm.errors.description" class="mt-1 text-sm text-destructive">
                      {{ createWebhookForm.errors.description }}
                    </p>
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <label class="block text-sm font-medium">Events</label>
                      <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" size="sm" @click="selectAllEvents(createWebhookForm)">Select All</Button>
                        <Button type="button" variant="outline" size="sm" @click="deselectAllEvents(createWebhookForm)">Deselect All</Button>
                      </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto border rounded-md p-2">
                      <div v-for="event in defaultEvents" :key="event" class="flex items-center space-x-2">
                        <Checkbox :id="`event-${event}`" :checked="createWebhookForm.events.includes(event)" @change="toggleEvent(event, createWebhookForm)" />
                        <label :for="`event-${event}`" class="text-sm">{{ event }}</label>
                      </div>
                    </div>
                    <p v-if="createWebhookForm.errors.events" class="mt-1 text-sm text-destructive">
                      {{ createWebhookForm.errors.events }}
                    </p>
                  </div>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" id="close-dialog" @click="$event.target.closest('dialog').close()">
                    Cancel
                  </Button>
                  <Button type="submit" :disabled="createWebhookForm.processing">
                    <span v-if="createWebhookForm.processing" class="mr-2">
                      <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </span>
                    Create Webhook
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

      <!-- Default webhook info -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Default Webhook Configuration</CardTitle>
          <CardDescription>
            This is the default webhook configuration for your application. Use this URL in your Stripe dashboard.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Webhook URL</p>
              <div class="mt-1 flex items-center gap-2">
                <code class="rounded bg-muted px-2 py-1 font-mono text-sm">{{ webhookUrl }}</code>
                <Button variant="ghost" size="icon" @click="copyToClipboard(webhookUrl)">
                  <Copy v-if="!copied" class="h-4 w-4" />
                  <Check v-else class="h-4 w-4 text-green-500" />
                </Button>
              </div>
            </div>
            <div v-if="webhookSecret">
              <p class="text-sm font-medium text-muted-foreground">Webhook Secret</p>
              <p class="mt-1 font-mono text-sm">{{ webhookSecret }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-muted-foreground">Default Events</p>
              <div class="mt-1 flex flex-wrap gap-2">
                <span v-for="event in defaultEvents" :key="event" class="rounded-full bg-muted px-2 py-1 text-xs">
                  {{ event }}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Webhooks table -->
      <Card>
        <CardHeader>
          <CardTitle>Webhook Endpoints</CardTitle>
          <CardDescription>
            Manage your Stripe webhook endpoints.
          </CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>URL</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Events</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="webhook in webhooks" :key="webhook.id">
                <TableCell>
                  <div class="font-mono text-xs">{{ webhook.url }}</div>
                </TableCell>
                <TableCell>
                  <span :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    webhook.status === 'enabled' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                  ]">
                    {{ webhook.status }}
                  </span>
                </TableCell>
                <TableCell>
                  <div class="flex flex-wrap gap-1">
                    <span v-if="webhook.events && webhook.events.length" class="rounded-full bg-muted px-2 py-1 text-xs">
                      {{ webhook.events.length }} events
                    </span>
                    <span v-else class="text-sm text-muted-foreground">No events</span>
                  </div>
                </TableCell>
                <TableCell>{{ new Date(webhook.created).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <Dialog>
                      <DialogTrigger asChild>
                        <Button variant="ghost" size="sm" @click="prepareUpdateWebhook(webhook)">
                          Edit
                        </Button>
                      </DialogTrigger>
                      <DialogContent class="sm:max-w-[600px]">
                        <DialogHeader>
                          <DialogTitle>Update Webhook Endpoint</DialogTitle>
                          <DialogDescription>
                            Update the events for this webhook endpoint.
                          </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitUpdateWebhook">
                          <div class="grid gap-4 py-4">
                            <div>
                              <label class="block text-sm font-medium mb-2">Webhook URL</label>
                              <div class="font-mono text-sm">{{ selectedWebhook?.url }}</div>
                            </div>
                            <div>
                              <label for="update-description" class="block text-sm font-medium mb-2">Description</label>
                              <Textarea id="update-description" v-model="updateWebhookForm.description" placeholder="Description for this webhook endpoint" />
                              <p v-if="updateWebhookForm.errors.description" class="mt-1 text-sm text-destructive">
                                {{ updateWebhookForm.errors.description }}
                              </p>
                            </div>
                            <div>
                              <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium">Events</label>
                                <div class="flex items-center gap-2">
                                  <Button type="button" variant="outline" size="sm" @click="selectAllEvents(updateWebhookForm)">Select All</Button>
                                  <Button type="button" variant="outline" size="sm" @click="deselectAllEvents(updateWebhookForm)">Deselect All</Button>
                                </div>
                              </div>
                              <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto border rounded-md p-2">
                                <div v-for="event in defaultEvents" :key="`update-${event}`" class="flex items-center space-x-2">
                                  <Checkbox :id="`update-event-${event}`" :checked="updateWebhookForm.events.includes(event)" @change="toggleEvent(event, updateWebhookForm)" />
                                  <label :for="`update-event-${event}`" class="text-sm">{{ event }}</label>
                                </div>
                              </div>
                              <p v-if="updateWebhookForm.errors.events" class="mt-1 text-sm text-destructive">
                                {{ updateWebhookForm.errors.events }}
                              </p>
                            </div>
                          </div>
                          <DialogFooter>
                            <Button type="button" variant="outline" id="close-update-dialog" @click="$event.target.closest('dialog').close()">
                              Cancel
                            </Button>
                            <Button type="submit" :disabled="updateWebhookForm.processing">
                              <span v-if="updateWebhookForm.processing" class="mr-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                              </span>
                              Update Webhook
                            </Button>
                          </DialogFooter>
                        </form>
                      </DialogContent>
                    </Dialog>
                    <Button variant="ghost" size="icon" @click="deleteWebhook(webhook.id)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="webhooks && webhooks.length === 0">
                <TableCell colspan="5" class="h-24 text-center">
                  No webhook endpoints found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
