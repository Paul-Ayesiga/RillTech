<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';
import {
  Mail,
  Send,
  Users,
  MessageSquare,
  Clock,
  CheckCircle,
  AlertTriangle
} from 'lucide-vue-next';

interface NewsletterSubscription {
  id: number;
  email: string;
  name?: string;
  status: 'active' | 'inactive' | 'unsubscribed';
  subscribed_at: string;
}

interface Props {
  open: boolean;
  selectedSubscriptions: NewsletterSubscription[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:open': [value: boolean];
  'sent': [count: number];
}>();

const isOpen = ref(props.open);
const isLoading = ref(false);

// Form data
const formData = ref({
  subject: '',
  email_content: '',
});

// Watch for prop changes
watch(() => props.open, (newValue) => {
  isOpen.value = newValue;
  if (newValue) {
    // Reset form when opening
    formData.value = {
      subject: '',
      email_content: '',
    };
  }
});

watch(isOpen, (newValue) => {
  emit('update:open', newValue);
});

// Computed properties
const activeSubscriptions = computed(() => {
  return props.selectedSubscriptions.filter(sub => sub.status === 'active');
});

const inactiveSubscriptions = computed(() => {
  return props.selectedSubscriptions.filter(sub => sub.status !== 'active');
});

const hasValidForm = computed(() => {
  return formData.value.subject.trim().length >= 3 &&
         formData.value.email_content.trim().length >= 10;
});

const canSendEmail = computed(() => {
  return hasValidForm.value && activeSubscriptions.value.length > 0;
});

// Methods
const handleSubmit = async () => {
  if (!canSendEmail.value) return;

  isLoading.value = true;
  try {
    await router.post('/admin/newsletter-subscriptions/bulk-email', {
      subscription_ids: activeSubscriptions.value.map(sub => sub.id),
      subject: formData.value.subject,
      email_content: formData.value.email_content,
    });

    emit('sent', activeSubscriptions.value.length);
    isOpen.value = false;
  } catch {
    toast.error('Error sending newsletter emails');
  } finally {
    isLoading.value = false;
  }
};

const handleCancel = () => {
  if (formData.value.subject.trim() || formData.value.email_content.trim()) {
    if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
      isOpen.value = false;
    }
  } else {
    isOpen.value = false;
  }
};

const getStatusBadge = (status: string) => {
  const statusMap = {
    'active': { icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-100' },
    'inactive': { icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-100' },
    'unsubscribed': { icon: AlertTriangle, color: 'text-red-600', bg: 'bg-red-100' }
  };
  return statusMap[status as keyof typeof statusMap] || statusMap.active;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
    <Drawer v-model:open="isOpen">
        <DrawerContent class="max-w-3xl mx-auto">
            <DrawerHeader>
                <DrawerTitle class="flex items-center gap-2">
                    <Mail class="h-5 w-5" />
                    Send Newsletter Email
                </DrawerTitle>
                <DrawerDescription>
                    Compose and send a newsletter email to selected subscribers.
                </DrawerDescription>
            </DrawerHeader>

            <div class="px-4 pb-4 space-y-6 max-h-[70vh] overflow-y-auto">
                <!-- Recipients Summary -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <Users class="h-4 w-4" />
                        Recipients Summary
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <CheckCircle class="h-5 w-5 text-green-600" />
                                <div>
                                    <p class="font-semibold text-green-900">Active Subscribers</p>
                                    <p class="text-2xl font-bold text-green-600">{{ activeSubscriptions.length }}</p>
                                    <p class="text-sm text-green-700">Will receive email</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <AlertTriangle class="h-5 w-5 text-yellow-600" />
                                <div>
                                    <p class="font-semibold text-yellow-900">Inactive/Unsubscribed</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ inactiveSubscriptions.length }}</p>
                                    <p class="text-sm text-yellow-700">Will be skipped</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-blue-600" />
                                <div>
                                    <p class="font-semibold text-blue-900">Total Selected</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ selectedSubscriptions.length }}</p>
                                    <p class="text-sm text-blue-700">Subscribers</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipients List -->
                    <div class="max-h-40 overflow-y-auto border rounded-lg">
                        <div class="p-3 bg-muted/50 border-b">
                            <h4 class="font-medium">Selected Recipients</h4>
                        </div>
                        <div class="p-3 space-y-2">
                            <div v-for="subscription in selectedSubscriptions" :key="subscription.id"
                                class="flex items-center justify-between py-2 border-b last:border-b-0 overflow-x-auto ">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <component :is="getStatusBadge(subscription.status).icon" class="h-4 w-4"
                                            :class="getStatusBadge(subscription.status).color" />
                                        <span class="text-sm font-medium">{{ subscription.email }}</span>
                                    </div>
                                    <span v-if="subscription.name" class="text-sm text-muted-foreground">
                                        ({{ subscription.name }})
                                    </span>
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="[getStatusBadge(subscription.status).bg, getStatusBadge(subscription.status).color]">
                                        {{ subscription.status }}
                                    </span>
                                    <span class="text-xs text-muted-foreground lg:flex hidden">
                                        {{ formatDate(subscription.subscribed_at) }}
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Email Composition -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <MessageSquare class="h-4 w-4" />
                        Email Content
                    </h3>

                    <!-- Subject -->
                    <div class="space-y-2">
                        <Label for="subject">Subject Line</Label>
                        <Input v-model="formData.subject" placeholder="Enter email subject..." maxlength="255" />
                        <p class="text-xs text-muted-foreground">
                            {{ formData.subject.length }}/255 characters
                        </p>
                    </div>

                    <!-- Email Content -->
                    <div class="space-y-2">
                        <Label for="email_content">Email Content</Label>
                        <Textarea v-model="formData.email_content" placeholder="Write your newsletter content here..."
                            rows="8" class="min-h-[200px]" maxlength="10000" />
                        <p class="text-xs text-muted-foreground">
                            {{ formData.email_content.length }}/10,000 characters. Minimum 10 characters required.
                        </p>
                    </div>

                    <!-- Email Preview Info -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <Mail class="h-5 w-5 text-blue-600 mt-0.5" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-blue-900">Email Preview</h4>
                                <div class="text-sm text-blue-700 mt-1 space-y-1">
                                    <p><strong>Subject:</strong> {{ formData.subject || 'No subject' }}</p>
                                    <p><strong>Recipients:</strong> {{ activeSubscriptions.length }} active subscribers
                                    </p>
                                    <p><strong>Delivery:</strong> Emails will be sent in the background with rate
                                        limiting</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DrawerFooter>
                <Button @click="handleSubmit" :disabled="!canSendEmail || isLoading" class="w-full">
                    <div v-if="isLoading"
                        class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent">
                    </div>
                    <Send v-else class="mr-2 h-4 w-4" />
                    {{
                    isLoading
                    ? 'Sending Newsletter...'
                    : `Send Newsletter to ${activeSubscriptions.length} Subscribers`
                    }}
                </Button>
                <DrawerClose as-child>
                    <Button variant="outline" @click="handleCancel" :disabled="isLoading">
                        Cancel
                    </Button>
                </DrawerClose>
            </DrawerFooter>
        </DrawerContent>
    </Drawer>
</template>
