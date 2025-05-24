<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import DataTableContactSubmissions from '@/components/admin/DataTableContactSubmissions.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'vue-sonner';
import {
  MessageSquare,
  Clock,
  AlertTriangle,
  Calendar,
  Mail
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
}

interface Stats {
  total: number;
  new: number;
  in_progress: number;
  resolved: number;
  urgent: number;
  today: number;
  this_week: number;
}

interface AdminUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

interface Props {
  submissions: {
    data: ContactSubmission[];
    links: any[];
    meta: any;
  };
  stats: Stats;
  adminUsers: AdminUser[];
}

const props = defineProps<Props>();

// Methods
const handleDeleteSubmission = async (submission: ContactSubmission) => {
  try {
    await router.delete(`/admin/contact-submissions/${submission.id}`);
    toast.success('Contact submission deleted successfully');
  } catch (error) {
    toast.error('Error deleting contact submission');
  }
};

const handleBulkDelete = async (submissions: ContactSubmission[]) => {
  if (submissions.length === 0) return;

  try {
    await router.post('/admin/contact-submissions/bulk-delete', {
      submission_ids: submissions.map(s => s.id)
    });
    toast.success('Contact submissions deleted successfully');
  } catch (error) {
    toast.error('Error deleting contact submissions');
  }
};

const handleExport = () => {
  window.open('/admin/contact-submissions/export-csv', '_blank');
};

const handleSubmissionUpdated = (updatedSubmission: ContactSubmission) => {
  // Show immediate feedback
  toast.success('Contact submission updated successfully');

  // Refresh the page to get updated data
  router.reload({ only: ['submissions', 'stats'] });
};
</script>

<template>
  <Head title="Contact Submissions" />

  <AdminLayout :breadcrumbs="[
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Contact Submissions', href: '#' }
  ]">
    <div class="space-y-6 px-3 py-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">Contact Submissions</h1>
          <p class="text-muted-foreground">Manage contact form submissions and customer inquiries</p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Submissions</CardTitle>
            <MessageSquare class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">All time submissions</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">New Submissions</CardTitle>
            <Clock class="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-blue-600">{{ stats.new.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">Awaiting response</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Urgent Priority</CardTitle>
            <AlertTriangle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.urgent.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">Needs immediate attention</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Today</CardTitle>
            <Calendar class="h-4 w-4 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-purple-600">{{ stats.today.toLocaleString() }}</div>
            <p class="text-xs text-muted-foreground">New today</p>
          </CardContent>
        </Card>
      </div>

      <!-- DataTable -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Mail class="h-5 w-5" />
            Contact Submissions
          </CardTitle>
        </CardHeader>
        <CardContent>
          <DataTableContactSubmissions
            :submissions="submissions.data"
            :admin-users="adminUsers"
            @delete="handleDeleteSubmission"
            @bulk-delete="handleBulkDelete"
            @export="handleExport"
            @updated="handleSubmissionUpdated"
          />
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
