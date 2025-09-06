<template>
    <AdminLayout>
        <div class="space-y-4 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demo Requests</h1>
                    <p class="text-gray-600 dark:text-gray-400">Manage and track demo scheduling requests</p>
                </div>
            </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 sm:p-6">
          <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
              <Clock class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 sm:p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <CheckCircle class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Confirmed</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.confirmed }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 sm:p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <TrendingUp class="h-6 w-6 text-purple-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Week</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.this_week }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div>
            <Label for="status">Status</Label>
            <Select v-model="filters.status" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">All statuses</SelectItem>
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
                <SelectItem :value="null">All types</SelectItem>
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
                <SelectItem :value="null">All sources</SelectItem>
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
      <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 sm:p-6">
        <div class="p-4 sm:p-6 border-b dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-lg font-semibold dark:text-white">Demo Requests</h2>
            <div class="flex items-center gap-2 self-end sm:self-auto">
              <Button variant="outline" size="sm" class="h-9 px-3 sm:h-9 sm:px-4" @click="exportData">
                <Download class="h-4 w-4 mr-1 sm:mr-2" />
                Export
              </Button>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto relative">
          <div class="min-w-[800px] w-full"> <!-- Ensure horizontal scroll on mobile -->
           <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-800/60">
              <tr>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-10">
                  <input
                    type="checkbox"
                    :checked="selectedRequests.length === demoRequests.data.length && demoRequests.data.length > 0"
                    @change="toggleSelectAll($event.target.checked)"
                    class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary/50"
                  />
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Contact
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Demo Details
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Preferred Time
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Source
                </th>
                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="request in demoRequests.data" :key="request.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                  <input
                    type="checkbox"
                    :checked="selectedRequests.includes(request.id)"
                    @change="toggleSelect(request.id)"
                    class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary/50"
                  />
                </td>
                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ request.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ request.email }}</div>
                    <div v-if="request.company" class="text-sm text-gray-500 dark:text-gray-400">{{ request.company }}</div>
                  </div>
                </td>
                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ request.demo_type_label }}</div>
                    <div v-if="request.message" class="text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                      {{ request.message }}
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
            </table>

            <!-- Bulk Actions -->
            <div v-if="selectedRequests.length > 0"
                class="fixed bottom-4 left-1/2 z-50 w-[95%] max-w-[600px] -translate-x-1/2 transform sm:bottom-6 sm:w-auto"
                >
                <div
                    class="flex flex-wrap items-center justify-center gap-2 rounded-lg border bg-white p-4 shadow-lg sm:gap-4 dark:border-gray-700 dark:bg-gray-800"
                >
                    <span class="w-full text-center text-sm text-gray-600 sm:w-auto dark:text-gray-300">{{ selectedRequests.length }} selected</span>
                    <Button variant="outline" size="sm" class="h-9 min-w-[90px] px-3 sm:h-9 sm:px-4" @click="bulkConfirm">
                        <CheckCircle class="mr-1 h-4 w-4 sm:mr-2" />
                        Confirm
                    </Button>
                    <Button variant="outline" size="sm" class="h-9 min-w-[90px] px-3 sm:h-9 sm:px-4" @click="bulkComplete">
                        <Check class="mr-1 h-4 w-4 sm:mr-2" />
                        Complete
                    </Button>
                    <Button variant="outline" size="sm" class="h-9 min-w-[90px] px-3 sm:h-9 sm:px-4" @click="bulkCancel">
                        <X class="mr-1 h-4 w-4 sm:mr-2" />
                        Cancel
                    </Button>
                    <Button variant="outline" size="sm" class="h-9 px-3 sm:h-9 sm:px-4" @click="clearSelection"> Clear </Button>
                </div>
            </div>
            </div>
        </div>
      </div>
        <!-- Status Edit Modal -->
        <Dialog v-model:open="showStatusModal">
            <DialogContent class="max-w-[95%] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Update Demo Status</DialogTitle>
                    <DialogDescription>
                        <span class="dark:text-gray-300">Update the status for {{ editingRequest?.name }}'s demo request.</span>
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
                        <Input v-model="statusForm.confirmed_datetime" type="datetime-local" :min="new Date().toISOString().slice(0, 16)" />
                    </div>

                    <div>
                        <Label for="admin_notes">Admin Notes</Label>
                        <Textarea v-model="statusForm.admin_notes" placeholder="Add any notes about this demo request..." rows="3" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showStatusModal = false">Cancel</Button>
                    <Button @click="updateStatus" :disabled="statusForm.processing">
                        <Loader2 v-if="statusForm.processing" class="mr-2 h-4 w-4 animate-spin" />
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
    demoRequests: any;
    stats: any;
    filters: any;
}

const props = defineProps<Props>();

// Reactive state
const selectedRequests = ref<number[]>([]);
const showStatusModal = ref(false);
const editingRequest = ref<any>(null);

const filters = ref({
    status: props.filters.status || null,
    demo_type: props.filters.demo_type || null,
    source: props.filters.source || null,
    search: props.filters.search || '',
});

const statusForm = useForm({
    status: '',
    confirmed_datetime: '',
    admin_notes: '',
});

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
        selectedRequests.value = props.demoRequests.data.map((request: any) => request.id);
    } else {
        selectedRequests.value = [];
    }
};

const toggleSelect = (id: number) => {
    const index = selectedRequests.value.indexOf(id);
    if (index > -1) {
        selectedRequests.value.splice(index, 1);
    } else {
        selectedRequests.value.push(id);
    }
};

const clearSelection = () => {
    selectedRequests.value = [];
};

const applyFilters = () => {
    // Convert null values to empty strings for the API request
    const apiFilters = {
        status: filters.value.status === null ? '' : filters.value.status,
        demo_type: filters.value.demo_type === null ? '' : filters.value.demo_type,
        source: filters.value.source === null ? '' : filters.value.source,
        search: filters.value.search,
    };

    router.get(route('admin.demo-requests.index'), apiFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
    // Convert null values to empty strings for the API request
    const apiFilters = {
        status: filters.value.status === null ? '' : filters.value.status,
        demo_type: filters.value.demo_type === null ? '' : filters.value.demo_type,
        source: filters.value.source === null ? '' : filters.value.source,
        search: filters.value.search,
        page,
    };

    router.get(route('admin.demo-requests.index'), apiFilters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewRequest = (request: any) => {
    router.visit(route('admin.demo-requests.show', request.id));
};

const editStatus = (request: any) => {
    editingRequest.value = request;
    statusForm.status = request.status;
    statusForm.confirmed_datetime = request.confirmed_datetime || '';
    statusForm.admin_notes = request.admin_notes || '';
    showStatusModal.value = true;
};

const updateStatus = () => {
    if (!editingRequest.value) return;

    // Use router.post instead of form.put to ensure Inertia response handling
    router.post(
        route('admin.demo-requests.update-status', editingRequest.value.id),
        {
            _method: 'PUT',
            status: statusForm.status,
            confirmed_datetime: statusForm.confirmed_datetime,
            admin_notes: statusForm.admin_notes,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Demo request status updated successfully');
                showStatusModal.value = false;
                editingRequest.value = null;
                statusForm.reset();
            },
            onError: (errors) => {
                toast.error('Failed to update demo request status: ' + Object.values(errors).flat().join(', '));
            },
        },
    );
};

const bulkConfirm = () => {
    if (selectedRequests.value.length === 0) return;

    router.post(
        route('admin.demo-requests.bulk-update'),
        {
            ids: selectedRequests.value,
            status: 'confirmed',
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`${selectedRequests.value.length} demo requests confirmed successfully`);
                selectedRequests.value = [];
            },
            onError: (errors) => {
                toast.error('Failed to confirm demo requests: ' + Object.values(errors).flat().join(', '));
            },
        },
    );
};

const bulkComplete = () => {
    if (selectedRequests.value.length === 0) return;

    router.post(
        route('admin.demo-requests.bulk-update'),
        {
            ids: selectedRequests.value,
            status: 'completed',
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`${selectedRequests.value.length} demo requests marked as completed`);
                selectedRequests.value = [];
            },
            onError: (errors) => {
                toast.error('Failed to complete demo requests: ' + Object.values(errors).flat().join(', '));
            },
        },
    );
};

const bulkCancel = () => {
    if (selectedRequests.value.length === 0) return;

    router.post(
        route('admin.demo-requests.bulk-update'),
        {
            ids: selectedRequests.value,
            status: 'cancelled',
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`${selectedRequests.value.length} demo requests cancelled successfully`);
                selectedRequests.value = [];
            },
            onError: (errors) => {
                toast.error('Failed to cancel demo requests: ' + Object.values(errors).flat().join(', '));
            },
        },
    );
};

const exportData = () => {
    // For file downloads, we use window.location to trigger a direct download
    // rather than an Inertia request
    const params = new URLSearchParams({
        status: filters.value.status === null ? '' : filters.value.status,
        demo_type: filters.value.demo_type === null ? '' : filters.value.demo_type,
        source: filters.value.source === null ? '' : filters.value.source,
        search: filters.value.search || '',
    }).toString();

    window.location.href = route('admin.demo-requests.export') + '?' + params;

    toast.success('Exporting demo requests data');
};
</script>
