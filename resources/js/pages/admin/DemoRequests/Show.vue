<template>
    <AdminLayout>
        <div class="space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" @click="$inertia.visit(route('admin.demo-requests.index'))">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Demo Requests
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demo Request #{{ demoRequest.id }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ demoRequest.name }} - {{ demoRequest.demo_type_label }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="getStatusVariant(demoRequest.status)" class="px-3 py-1 text-sm">
                        {{ demoRequest.status }}
                    </Badge>
                    <Button size="sm" class="sm:size-md" @click="editStatus">
                        <Edit class="mr-2 h-4 w-4" />
                        <span class="hidden sm:inline">Update Status</span>
                        <span class="sm:hidden">Update</span>
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-4 sm:space-y-6 lg:col-span-2">
                    <!-- Contact Information -->
                    <div class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">Contact Information</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.email }}</p>
                            </div>
                            <div v-if="demoRequest.company">
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.company }}</p>
                            </div>
                            <div v-if="demoRequest.phone">
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Details -->
                    <div class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">Demo Details</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Demo Type</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.demo_type_label }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Source</Label>
                                <Badge variant="outline">{{ demoRequest.source }}</Badge>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Preferred Date & Time</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.formatted_preferred_datetime }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ demoRequest.timezone }}</p>
                            </div>
                            <div v-if="demoRequest.confirmed_datetime">
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmed Date & Time</Label>
                                <p class="text-gray-900 dark:text-white">{{ formatDateTime(demoRequest.confirmed_datetime) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ demoRequest.timezone }}</p>
                            </div>
                        </div>

                        <div v-if="demoRequest.message" class="mt-4">
                            <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Message</Label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ demoRequest.message }}</p>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">Admin Notes</h2>
                        <div v-if="demoRequest.admin_notes">
                            <p class="text-gray-900 dark:text-white">{{ demoRequest.admin_notes }}</p>
                        </div>
                        <div v-else>
                            <p class="text-gray-500 italic dark:text-gray-400">No admin notes yet.</p>
                        </div>
                    </div>

                    <!-- Metadata (if from chatbot) -->
                    <div
                        v-if="demoRequest.metadata && Object.keys(demoRequest.metadata).length > 0"
                        class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">Additional Information</h2>
                        <div class="space-y-2">
                            <div v-for="(value, key) in demoRequest.metadata" :key="key">
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ formatMetadataKey(key) }}</Label>
                                <p class="text-gray-900 dark:text-white">{{ value }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- Quick Actions -->
                    <div class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">Quick Actions</h3>
                        <div class="space-y-3">
                            <Button v-if="demoRequest.status === 'pending'" @click="quickUpdateStatus('confirmed')" class="w-full">
                                <CheckCircle class="mr-2 h-4 w-4" />
                                Confirm Demo
                            </Button>

                            <Button
                                v-if="demoRequest.status === 'confirmed'"
                                @click="quickUpdateStatus('completed')"
                                class="w-full"
                                variant="secondary"
                            >
                                <Clock class="mr-2 h-4 w-4" />
                                Mark Completed
                            </Button>

                            <Button
                                v-if="['pending', 'confirmed'].includes(demoRequest.status)"
                                @click="quickUpdateStatus('cancelled')"
                                class="w-full"
                                variant="destructive"
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                Cancel Demo
                            </Button>

                            <Button variant="outline" class="w-full" @click="sendEmail">
                                <Mail class="mr-2 h-4 w-4" />
                                Send Email
                            </Button>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg dark:text-white">Timeline</h3>
                        <div class="relative space-y-6 border-l-2 border-gray-200 pl-5 dark:border-gray-600">
                            <!-- Demo Requested -->
                            <div class="relative">
                                <div class="absolute -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-blue-500 dark:border-gray-800"></div>
                                <div>
                                    <p class="text-sm font-medium dark:text-white">Demo Requested</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(demoRequest.created_at) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">via {{ demoRequest.source }}</p>
                                </div>
                            </div>

                            <!-- Demo Confirmed -->
                            <div v-if="demoRequest.confirmed_datetime" class="relative">
                                <div class="absolute -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-green-500 dark:border-gray-800"></div>
                                <div>
                                    <p class="text-sm font-medium dark:text-white">Demo Confirmed</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(demoRequest.confirmed_datetime) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatTime(demoRequest.confirmed_datetime) }}</p>
                                </div>
                            </div>

                            <!-- Demo Completed -->
                            <div v-if="demoRequest.status === 'completed'" class="relative">
                                <div
                                    class="absolute -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-purple-500 dark:border-gray-800"
                                ></div>
                                <div>
                                    <p class="text-sm font-medium dark:text-white">Demo Completed</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(demoRequest.updated_at) }}</p>
                                </div>
                            </div>

                            <!-- Demo Cancelled -->
                            <div v-if="demoRequest.status === 'cancelled'" class="relative">
                                <div class="absolute -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-red-500 dark:border-gray-800"></div>
                                <div>
                                    <p class="text-sm font-medium dark:text-white">Demo Cancelled</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(demoRequest.updated_at) }}</p>
                                </div>
                            </div>

                            <!-- Demo Rescheduled -->
                            <div v-if="demoRequest.status === 'rescheduled'" class="relative">
                                <div
                                    class="absolute -left-[21px] h-4 w-4 rounded-full border-2 border-white bg-yellow-500 dark:border-gray-800"
                                ></div>
                                <div>
                                    <p class="text-sm font-medium dark:text-white">Demo Rescheduled</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(demoRequest.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Information -->
                    <div v-if="demoRequest.user" class="rounded-lg border bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-base font-semibold sm:mb-4 sm:text-lg">User Account</h3>
                        <div class="space-y-2">
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.user.name }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</Label>
                                <p class="text-gray-900 dark:text-white">{{ demoRequest.user.email }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</Label>
                                <p class="text-gray-900 dark:text-white">{{ formatDate(demoRequest.user.created_at) }}</p>
                            </div>
                        </div>
                        <Button variant="outline" class="mt-4 w-full" @click="viewUser">
                            <User class="mr-2 h-4 w-4" />
                            View User Profile
                        </Button>
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
                        <span class="dark:text-gray-300">Update the status for {{ demoRequest.name }}'s demo request.</span>
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
                                <SelectItem value="no-show">No Show</SelectItem>
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, Edit, Loader2, Mail, User, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
    demoRequest: any;
}

const props = defineProps<Props>();

// Reactive state
const showStatusModal = ref(false);

const statusForm = useForm({
    status: props.demoRequest.status,
    confirmed_datetime: props.demoRequest.confirmed_datetime || '',
    admin_notes: props.demoRequest.admin_notes || '',
});

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'confirmed':
            return 'default';
        case 'completed':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        case 'rescheduled':
            return 'outline';
        default:
            return 'secondary';
    }
};

const formatDate = (date: string) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
};

const formatTime = (date: string) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(date));
};

const formatDateTime = (date: string) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(date));
};

const formatMetadataKey = (key: string) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const editStatus = () => {
    showStatusModal.value = true;
};

const quickUpdateStatus = (status: string) => {
    statusForm.status = status;
    statusForm.patch(route('admin.demo-requests.update', props.demoRequest.id), {
        onSuccess: () => {
            toast.success('Status updated successfully!');
        },
        onError: () => {
            toast.error('Failed to update status');
        },
    });
};

const updateStatus = () => {
    statusForm.patch(route('admin.demo-requests.update', props.demoRequest.id), {
        onSuccess: () => {
            showStatusModal.value = false;
            toast.success('Demo request updated successfully!');
        },
        onError: () => {
            toast.error('Failed to update demo request');
        },
    });
};

const sendEmail = () => {
    // TODO: Implement email sending functionality
    toast.info('Email functionality coming soon!');
};

const viewUser = () => {
    if (props.demoRequest.user) {
        router.visit(route('admin.users.show', props.demoRequest.user.id));
    }
};
</script>
