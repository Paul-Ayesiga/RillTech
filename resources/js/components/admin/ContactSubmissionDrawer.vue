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
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';
import {
  User,
  Mail,
  Phone,
  Building,
  Calendar,
  Clock,
  CheckCircle,
  AlertTriangle,
  UserCheck,
  MessageSquare,
  Settings,
  Send,
  Reply
} from 'lucide-vue-next';

interface ContactSubmission {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  company: string | null;
  subject: string;
  message: string;
  status: 'new' | 'in_progress' | 'resolved' | 'closed';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  source: string | null;
  created_at: string;
  responded_at: string | null;
  assigned_to: number | null;
  assigned_user?: {
    id: number;
    name: string;
    email: string;
  };
  admin_notes?: string;
}

interface AdminUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

interface Props {
  open: boolean;
  submission: ContactSubmission | null;
  adminUsers: AdminUser[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:open': [value: boolean];
  'updated': [submission: ContactSubmission];
}>();

const isOpen = ref(props.open);
const isLoading = ref(false);

// Form data
const formData = ref({
  status: '',
  priority: '',
  assigned_to: null as number | null,
  admin_notes: '',
});

// Email reply data
const emailReplyData = ref({
  reply_message: '',
  send_email: false,
});

const showEmailReply = ref(false);

// Watch for prop changes
watch(() => props.open, (newValue) => {
  isOpen.value = newValue;
  if (newValue && props.submission) {
    // Initialize form with current submission data
    formData.value = {
      status: props.submission.status,
      priority: props.submission.priority,
      assigned_to: props.submission.assigned_to,
      admin_notes: props.submission.admin_notes || '',
    };

    // Reset email reply data
    emailReplyData.value = {
      reply_message: '',
      send_email: false,
    };
    showEmailReply.value = false;
  }
});

watch(isOpen, (newValue) => {
  emit('update:open', newValue);
});

// Computed properties
const statusOptions = [
  { value: 'new', label: 'New', icon: Clock, color: 'text-blue-600' },
  { value: 'in_progress', label: 'In Progress', icon: AlertTriangle, color: 'text-yellow-600' },
  { value: 'resolved', label: 'Resolved', icon: CheckCircle, color: 'text-green-600' },
  { value: 'closed', label: 'Closed', icon: CheckCircle, color: 'text-gray-600' }
];

const priorityOptions = [
  { value: 'low', label: 'Low', color: 'text-gray-600' },
  { value: 'medium', label: 'Medium', color: 'text-blue-600' },
  { value: 'high', label: 'High', color: 'text-orange-600' },
  { value: 'urgent', label: 'Urgent', color: 'text-red-600' }
];

const adminUsersOptions = computed(() => {
  return props.adminUsers.filter(user =>
    user.roles.includes('admin') || user.roles.includes('super-admin')
  );
});

const hasChanges = computed(() => {
  if (!props.submission) return false;

  return (
    formData.value.status !== props.submission.status ||
    formData.value.priority !== props.submission.priority ||
    formData.value.assigned_to !== props.submission.assigned_to ||
    formData.value.admin_notes !== (props.submission.admin_notes || '') ||
    emailReplyData.value.reply_message.trim() !== ''
  );
});

const canSendEmail = computed(() => {
  return emailReplyData.value.reply_message.trim().length >= 10;
});

// Methods
const handleSubmit = async () => {
  if (!props.submission || !hasChanges.value) return;

  isLoading.value = true;
  try {
    // If there's an email reply, send it via the reply endpoint
    if (emailReplyData.value.reply_message.trim()) {
      await router.post(`/admin/contact-submissions/${props.submission.id}/reply`, {
        ...formData.value,
        reply_message: emailReplyData.value.reply_message,
      });
    } else {
      // Otherwise, just update the submission
      await router.patch(`/admin/contact-submissions/${props.submission.id}`, formData.value);
    }

    emit('updated', { ...props.submission, ...formData.value } as ContactSubmission);
    isOpen.value = false;
  } catch {
    toast.error('Error updating contact submission');
  } finally {
    isLoading.value = false;
  }
};

const handleCancel = () => {
  if (hasChanges.value) {
    if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
      isOpen.value = false;
    }
  } else {
    isOpen.value = false;
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <Drawer v-model:open="isOpen">
    <DrawerContent class="max-w-2xl mx-auto">
      <DrawerHeader>
        <DrawerTitle class="flex items-center gap-2">
          <Settings class="h-5 w-5" />
          Manage Contact Submission
        </DrawerTitle>
        <DrawerDescription>
          Update status, priority, assignment, and add admin notes for this contact submission.
        </DrawerDescription>
      </DrawerHeader>

      <div class="px-4 pb-4 space-y-6 max-h-[70vh] overflow-y-auto">
        <!-- Contact Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <User class="h-4 w-4" />
            Contact Information
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-muted/50 rounded-lg">
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <User class="h-4 w-4 text-muted-foreground" />
                <span class="font-medium">{{ submission?.name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Mail class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm">{{ submission?.email }}</span>
              </div>
              <div v-if="submission?.phone" class="flex items-center gap-2">
                <Phone class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm">{{ submission.phone }}</span>
              </div>
              <div v-if="submission?.company" class="flex items-center gap-2">
                <Building class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm">{{ submission.company }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm">{{ submission ? formatDate(submission.created_at) : '' }}</span>
              </div>
              <div class="flex items-center gap-2">
                <MessageSquare class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm font-medium">{{ submission?.subject }}</span>
              </div>
            </div>
          </div>

          <!-- Message -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Message</Label>
            <div class="p-3 bg-muted/30 rounded-md text-sm">
              {{ submission?.message }}
            </div>
          </div>
        </div>

        <Separator />

        <!-- Management Form -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Management</h3>

          <!-- Status and Priority -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="status">Status</Label>
              <Select v-model="formData.status">
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                    <div class="flex items-center gap-2">
                      <component :is="option.icon" class="h-4 w-4" :class="option.color" />
                      {{ option.label }}
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="priority">Priority</Label>
              <Select v-model="formData.priority">
                <SelectTrigger>
                  <SelectValue placeholder="Select priority" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="option in priorityOptions" :key="option.value" :value="option.value">
                    <div class="flex items-center gap-2">
                      <div class="w-2 h-2 rounded-full" :class="option.color.replace('text-', 'bg-')"></div>
                      {{ option.label }}
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Assignment -->
          <div class="space-y-2">
            <Label for="assigned_to">Assign to Admin</Label>
            <Select v-model="formData.assigned_to">
              <SelectTrigger>
                <SelectValue placeholder="Select admin user" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">Unassigned</SelectItem>
                <SelectItem v-for="user in adminUsersOptions" :key="user.id" :value="user.id">
                  <div class="flex items-center gap-2">
                    <UserCheck class="h-4 w-4" />
                    {{ user.name }} ({{ user.email }})
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Admin Notes -->
          <div class="space-y-2">
            <Label for="admin_notes">Admin Notes</Label>
            <Textarea
              v-model="formData.admin_notes"
              placeholder="Add internal notes about this submission..."
              rows="3"
            />
          </div>
        </div>

        <Separator />

        <!-- Email Reply Section -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold flex items-center gap-2">
              <Reply class="h-4 w-4" />
              Email Reply
            </h3>
            <Button
              variant="outline"
              size="sm"
              @click="showEmailReply = !showEmailReply"
              type="button"
            >
              <Mail class="mr-2 h-4 w-4" />
              {{ showEmailReply ? 'Hide Reply' : 'Send Reply' }}
            </Button>
          </div>

          <div v-if="showEmailReply" class="space-y-4">
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <Mail class="h-5 w-5 text-blue-600 mt-0.5" />
                </div>
                <div class="flex-1">
                  <h4 class="font-medium text-blue-900">Email Reply</h4>
                  <p class="text-sm text-blue-700 mt-1">
                    This will send an email reply to {{ submission?.email }} and automatically update the submission status.
                  </p>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="reply_message">Reply Message</Label>
              <Textarea
                v-model="emailReplyData.reply_message"
                placeholder="Type your reply message here..."
                rows="6"
                class="min-h-[120px]"
              />
              <p class="text-xs text-muted-foreground">
                Minimum 10 characters required. The email will be sent in the background.
              </p>
            </div>

            <div class="p-3 bg-muted/30 rounded-md text-sm">
              <p class="font-medium mb-2">Email Preview:</p>
              <p><strong>To:</strong> {{ submission?.email }}</p>
              <p><strong>Subject:</strong> Re: {{ submission?.subject }}</p>
              <p><strong>From:</strong> Customer Support Team</p>
            </div>
          </div>
        </div>
      </div>

      <DrawerFooter>
        <Button
          @click="handleSubmit"
          :disabled="!hasChanges || isLoading || (showEmailReply && emailReplyData.reply_message.trim().length < 10)"
          class="w-full"
        >
          <div v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
          <Send v-else-if="emailReplyData.reply_message.trim()" class="mr-2 h-4 w-4" />
          <Settings v-else class="mr-2 h-4 w-4" />
          {{
            isLoading
              ? (emailReplyData.reply_message.trim() ? 'Sending Reply...' : 'Updating...')
              : (emailReplyData.reply_message.trim() ? 'Send Reply & Update' : 'Update Submission')
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
