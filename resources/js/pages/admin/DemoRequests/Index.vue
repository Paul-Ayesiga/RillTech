<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Demo Requests</h1>
          <p class="text-gray-600">Manage and track demo scheduling requests</p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <Calendar class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Requests</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <Clock class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Pending</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.pending }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <CheckCircle class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Confirmed</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.confirmed }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <TrendingUp class="h-6 w-6 text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">This Week</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.this_week }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg border p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <Label for="status">Status</Label>
            <Select v-model="filters.status" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="confirmed">Confirmed</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
                <SelectItem value="rescheduled">Rescheduled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label for="demo_type">Demo Type</Label>
            <Select v-model="filters.demo_type" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All types</SelectItem>
                <SelectItem value="general">General</SelectItem>
                <SelectItem value="enterprise">Enterprise</SelectItem>
                <SelectItem value="specific-feature">Feature-Specific</SelectItem>
                <SelectItem value="custom">Custom</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label for="source">Source</Label>
            <Select v-model="filters.source" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All sources" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All sources</SelectItem>
                <SelectItem value="manual">Manual</SelectItem>
                <SelectItem value="chatbot">Chatbot</SelectItem>
                <SelectItem value="api">API</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label for="search">Search</Label>
            <Input
              v-model="filters.search"
              placeholder="Search by name, email, or company..."
              @input="debounceSearch"
            />
          </div>
        </div>
      </div>

      <!-- Demo Requests Table -->
      <div class="bg-white rounded-lg border">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Demo Requests</h2>
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" @click="exportData">
                <Download class="h-4 w-4 mr-2" />
                Export
              </Button>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <Checkbox 
                    :checked="selectedRequests.length === demoRequests.data.length && demoRequests.data.length > 0"
                    @update:checked="toggleSelectAll"
                  />
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contact
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Demo Details
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Preferred Time
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Source
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="request in demoRequests.data" :key="request.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <Checkbox 
                    :checked="selectedRequests.includes(request.id)"
                    @update:checked="toggleSelect(request.id)"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ request.name }}</div>
                    <div class="text-sm text-gray-500">{{ request.email }}</div>
                    <div v-if="request.company" class="text-sm text-gray-500">{{ request.company }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ request.demo_type_label }}</div>
                    <div v-if="request.message" class="text-sm text-gray-500 max-w-xs truncate">
                      {{ request.message }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ request.formatted_preferred_datetime }}</div>
                  <div class="text-sm text-gray-500">{{ request.timezone }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <Badge :variant="getStatusVariant(request.status)">
                    {{ request.status }}
                  </Badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <Badge variant="outline">
                    {{ request.source }}
                  </Badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="viewRequest(request)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="editStatus(request)">
                      <Edit class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Showing {{ demoRequests.from }} to {{ demoRequests.to }} of {{ demoRequests.total }} results
            </div>
            <div class="flex items-center gap-2">
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="!demoRequests.prev_page_url"
                @click="goToPage(demoRequests.current_page - 1)"
              >
                Previous
              </Button>
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="!demoRequests.next_page_url"
                @click="goToPage(demoRequests.current_page + 1)"
              >
                Next
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedRequests.length > 0" class="fixed bottom-6 left-1/2 transform -translate-x-1/2">
        <div class="bg-white rounded-lg shadow-lg border p-4 flex items-center gap-4">
          <span class="text-sm text-gray-600">{{ selectedRequests.length }} selected</span>
          <Button variant="outline" size="sm" @click="bulkConfirm">
            <CheckCircle class="h-4 w-4 mr-2" />
            Confirm
          </Button>
          <Button variant="outline" size="sm" @click="bulkComplete">
            <Check class="h-4 w-4 mr-2" />
            Complete
          </Button>
          <Button variant="outline" size="sm" @click="bulkCancel">
            <X class="h-4 w-4 mr-2" />
            Cancel
          </Button>
          <Button variant="outline" size="sm" @click="clearSelection">
            Clear
          </Button>
        </div>
      </div>
    </div>

    <!-- Status Edit Modal -->
    <Dialog v-model:open="showStatusModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Update Demo Status</DialogTitle>
          <DialogDescription>
            Update the status for {{ editingRequest?.name }}'s demo request.
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
import { ref, computed, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Checkbox } from '@/Components/ui/checkbox'
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
  Calendar,
  Clock,
  CheckCircle,
  TrendingUp,
  Download,
  Eye,
  Edit,
  Check,
  X,
  Loader2
} from 'lucide-vue-next'

interface Props {
  demoRequests: any
  stats: any
  filters: any
}

const props = defineProps<Props>()

// Reactive state
const selectedRequests = ref<number[]>([])
const showStatusModal = ref(false)
const editingRequest = ref<any>(null)

const filters = ref({
  status: props.filters.status || '',
  demo_type: props.filters.demo_type || '',
  source: props.filters.source || '',
  search: props.filters.search || ''
})

const statusForm = useForm({
  status: '',
  confirmed_datetime: '',
  admin_notes: ''
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

const toggleSelectAll = (checked: boolean) => {
  if (checked) {
    selectedRequests.value = props.demoRequests.data.map((request: any) => request.id)
  } else {
    selectedRequests.value = []
  }
}

const toggleSelect = (id: number) => {
  const index = selectedRequests.value.indexOf(id)
  if (index > -1) {
    selectedRequests.value.splice(index, 1)
  } else {
    selectedRequests.value.push(id)
  }
}

const clearSelection = () => {
  selectedRequests.value = []
}

const applyFilters = () => {
  router.get(route('admin.demo-requests.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const debounceSearch = (() => {
  let timeout: NodeJS.Timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 500)
  }
})()

const goToPage = (page: number) => {
  router.get(route('admin.demo-requests.index'), { ...filters.value, page }, {
    preserveState: true,
    preserveScroll: true
  })
}

const viewRequest = (request: any) => {
  router.visit(route('admin.demo-requests.show', request.id))
}

const editStatus = (request: any) => {
  editingRequest.value = request
  statusForm.status = request.status
  statusForm.confirmed_datetime = request.confirmed_datetime || ''
  statusForm.admin_notes = request.admin_notes || ''
  showStatusModal.value = true
}

const updateStatus = () => {
  if (!editingRequest.value) return

  statusForm.put(route('admin.demo-requests.update-status', editingRequest.value.id), {
    onSuccess: () => {
      toast.success('Demo request status updated successfully')
      showStatusModal.value = false
      editingRequest.value = null
    },
    onError: () => {
      toast.error('Failed to update demo request status')
    }
  })
}

const bulkConfirm = () => {
  // Implementation for bulk confirm
  console.log('Bulk confirm:', selectedRequests.value)
}

const bulkComplete = () => {
  // Implementation for bulk complete
  console.log('Bulk complete:', selectedRequests.value)
}

const bulkCancel = () => {
  // Implementation for bulk cancel
  console.log('Bulk cancel:', selectedRequests.value)
}

const exportData = () => {
  // Implementation for export
  console.log('Export data')
}
</script>
