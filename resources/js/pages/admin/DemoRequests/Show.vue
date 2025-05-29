<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" @click="$inertia.visit(route('admin.demo-requests.index'))">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back to Demo Requests
          </Button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Demo Request #{{ demoRequest.id }}</h1>
            <p class="text-gray-600">{{ demoRequest.name }} - {{ demoRequest.demo_type_label }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Badge :variant="getStatusVariant(demoRequest.status)" class="text-sm">
            {{ demoRequest.status }}
          </Badge>
          <Button @click="editStatus">
            <Edit class="h-4 w-4 mr-2" />
            Update Status
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Contact Information -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">Contact Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-gray-500">Name</Label>
                <p class="text-gray-900">{{ demoRequest.name }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-gray-500">Email</Label>
                <p class="text-gray-900">{{ demoRequest.email }}</p>
              </div>
              <div v-if="demoRequest.company">
                <Label class="text-sm font-medium text-gray-500">Company</Label>
                <p class="text-gray-900">{{ demoRequest.company }}</p>
              </div>
              <div v-if="demoRequest.phone">
                <Label class="text-sm font-medium text-gray-500">Phone</Label>
                <p class="text-gray-900">{{ demoRequest.phone }}</p>
              </div>
            </div>
          </div>

          <!-- Demo Details -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">Demo Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-gray-500">Demo Type</Label>
                <p class="text-gray-900">{{ demoRequest.demo_type_label }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-gray-500">Source</Label>
                <Badge variant="outline">{{ demoRequest.source }}</Badge>
              </div>
              <div>
                <Label class="text-sm font-medium text-gray-500">Preferred Date & Time</Label>
                <p class="text-gray-900">{{ demoRequest.formatted_preferred_datetime }}</p>
                <p class="text-sm text-gray-500">{{ demoRequest.timezone }}</p>
              </div>
              <div v-if="demoRequest.confirmed_datetime">
                <Label class="text-sm font-medium text-gray-500">Confirmed Date & Time</Label>
                <p class="text-gray-900">{{ demoRequest.formatted_confirmed_datetime }}</p>
              </div>
            </div>
            
            <div v-if="demoRequest.message" class="mt-4">
              <Label class="text-sm font-medium text-gray-500">Message</Label>
              <p class="text-gray-900 mt-1">{{ demoRequest.message }}</p>
            </div>
          </div>

          <!-- Admin Notes -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">Admin Notes</h2>
            <div v-if="demoRequest.admin_notes">
              <p class="text-gray-900">{{ demoRequest.admin_notes }}</p>
            </div>
            <div v-else>
              <p class="text-gray-500 italic">No admin notes yet.</p>
            </div>
          </div>

          <!-- Metadata (if from chatbot) -->
          <div v-if="demoRequest.metadata && Object.keys(demoRequest.metadata).length > 0" class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">Additional Information</h2>
            <div class="space-y-2">
              <div v-for="(value, key) in demoRequest.metadata" :key="key">
                <Label class="text-sm font-medium text-gray-500">{{ formatMetadataKey(key) }}</Label>
                <p class="text-gray-900">{{ value }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quick Actions -->
          <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-3">
              <Button 
                v-if="demoRequest.status === 'pending'" 
                @click="quickUpdateStatus('confirmed')" 
                class="w-full"
              >
                <CheckCircle class="h-4 w-4 mr-2" />
                Confirm Demo
              </Button>
              
              <Button 
                v-if="demoRequest.status === 'confirmed'" 
                @click="quickUpdateStatus('completed')" 
                variant="outline" 
                class="w-full"
              >
                <Check class="h-4 w-4 mr-2" />
                Mark as Completed
              </Button>
              
              <Button 
                v-if="['pending', 'confirmed'].includes(demoRequest.status)" 
                @click="quickUpdateStatus('cancelled')" 
                variant="destructive" 
                class="w-full"
              >
                <X class="h-4 w-4 mr-2" />
                Cancel Demo
              </Button>

              <Button variant="outline" class="w-full" @click="sendEmail">
                <Mail class="h-4 w-4 mr-2" />
                Send Email
              </Button>
            </div>
          </div>

          <!-- Timeline -->
          <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold mb-4">Timeline</h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                <div>
                  <p class="text-sm font-medium">Demo Requested</p>
                  <p class="text-xs text-gray-500">{{ formatDate(demoRequest.created_at) }}</p>
                  <p class="text-xs text-gray-500">via {{ demoRequest.source }}</p>
                </div>
              </div>
              
              <div v-if="demoRequest.confirmed_datetime" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                <div>
                  <p class="text-sm font-medium">Demo Confirmed</p>
                  <p class="text-xs text-gray-500">{{ formatDate(demoRequest.updated_at) }}</p>
                </div>
              </div>
              
              <div v-if="demoRequest.status === 'completed'" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                <div>
                  <p class="text-sm font-medium">Demo Completed</p>
                  <p class="text-xs text-gray-500">{{ formatDate(demoRequest.updated_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- User Information -->
          <div v-if="demoRequest.user" class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold mb-4">User Account</h3>
            <div class="space-y-2">
              <div>
                <Label class="text-sm font-medium text-gray-500">Name</Label>
                <p class="text-gray-900">{{ demoRequest.user.name }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-gray-500">Email</Label>
                <p class="text-gray-900">{{ demoRequest.user.email }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-gray-500">Joined</Label>
                <p class="text-gray-900">{{ formatDate(demoRequest.user.created_at) }}</p>
              </div>
              <Button variant="outline" size="sm" class="w-full mt-3" @click="viewUser">
                <User class="h-4 w-4 mr-2" />
                View User Profile
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <Dialog v-model:open="showStatusModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Update Demo Status</DialogTitle>
          <DialogDescription>
            Update the status for {{ demoRequest.name }}'s demo request.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div>
            <Label for="status">Status</Label>
            <Select v-model="statusForm.status">
              <SelectTrigger>
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="confirmed">Confirmed</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
                <SelectItem value="rescheduled">Rescheduled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div v-if="statusForm.status === 'confirmed'">
            <Label for="confirmed_datetime">Confirmed Date & Time</Label>
            <Input
              v-model="statusForm.confirmed_datetime"
              type="datetime-local"
              :min="new Date().toISOString().slice(0, 16)"
            />
          </div>

          <div>
            <Label for="admin_notes">Admin Notes</Label>
            <Textarea
              v-model="statusForm.admin_notes"
              placeholder="Add any notes about this demo request..."
              rows="3"
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showStatusModal = false">Cancel</Button>
          <Button @click="updateStatus" :disabled="statusForm.processing">
            <Loader2 v-if="statusForm.processing" class="h-4 w-4 mr-2 animate-spin" />
            Update Status
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Badge } from '@/Components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import {
  ArrowLeft,
  Edit,
  CheckCircle,
  Check,
  X,
  Mail,
  User,
  Loader2
} from 'lucide-vue-next'

interface Props {
  demoRequest: any
}

const props = defineProps<Props>()

// Reactive state
const showStatusModal = ref(false)

const statusForm = useForm({
  status: props.demoRequest.status,
  confirmed_datetime: props.demoRequest.confirmed_datetime || '',
  admin_notes: props.demoRequest.admin_notes || ''
})

// Methods
const getStatusVariant = (status: string) => {
  const variants = {
    pending: 'secondary',
    confirmed: 'default',
    completed: 'success',
    cancelled: 'destructive',
    rescheduled: 'warning'
  }
  return variants[status] || 'secondary'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatMetadataKey = (key: string) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const editStatus = () => {
  showStatusModal.value = true
}

const quickUpdateStatus = (status: string) => {
  statusForm.status = status
  updateStatus()
}

const updateStatus = () => {
  statusForm.put(route('admin.demo-requests.update-status', props.demoRequest.id), {
    onSuccess: () => {
      toast.success('Demo request status updated successfully')
      showStatusModal.value = false
    },
    onError: () => {
      toast.error('Failed to update demo request status')
    }
  })
}

const sendEmail = () => {
  // Implementation for sending email
  toast.info('Email functionality coming soon')
}

const viewUser = () => {
  if (props.demoRequest.user) {
    // Navigate to user profile
    window.open(route('admin.users.show', props.demoRequest.user.id), '_blank')
  }
}
</script>
