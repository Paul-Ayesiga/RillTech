<script setup lang="ts">
import { ref, computed, h } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
  type ColumnDef,
  type SortingState,
  type ColumnFiltersState,
  type VisibilityState,
} from '@tanstack/vue-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import ContactSubmissionDrawer from '@/components/admin/ContactSubmissionDrawer.vue';
import { toast } from 'sonner';
import {
  ArrowUpDown,
  MoreHorizontal,
  Trash2,
  Settings,
  Clock,
  CheckCircle,
  AlertTriangle,
  UserCheck,
  Download,
  Search
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

interface AdminUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

interface Props {
  submissions: ContactSubmission[];
  adminUsers: AdminUser[];
  onDelete?: (submission: ContactSubmission) => void;
  onBulkDelete?: (submissions: ContactSubmission[]) => void;
  onExport?: () => void;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  delete: [submission: ContactSubmission];
  bulkDelete: [submissions: ContactSubmission[]];
  export: [];
  updated: [submission: ContactSubmission];
}>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const globalFilter = ref('');

// Modal states
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const submissionToDelete = ref<ContactSubmission | null>(null);
const submissionsToDelete = ref<ContactSubmission[]>([]);
const isDeleting = ref(false);

// Drawer states
const showManageDrawer = ref(false);
const submissionToManage = ref<ContactSubmission | null>(null);

const getStatusBadge = (status: string) => {
  const statusMap = {
    'new': { variant: 'default' as const, text: 'New', icon: Clock },
    'in_progress': { variant: 'secondary' as const, text: 'In Progress', icon: AlertTriangle },
    'resolved': { variant: 'outline' as const, text: 'Resolved', icon: CheckCircle },
    'closed': { variant: 'secondary' as const, text: 'Closed', icon: CheckCircle }
  };
  return statusMap[status as keyof typeof statusMap] || statusMap.new;
};

const getPriorityBadge = (priority: string) => {
  const priorityMap = {
    'low': { variant: 'outline' as const, text: 'Low' },
    'medium': { variant: 'secondary' as const, text: 'Medium' },
    'high': { variant: 'destructive' as const, text: 'High' },
    'urgent': { variant: 'destructive' as const, text: 'Urgent' }
  };
  return priorityMap[priority as keyof typeof priorityMap] || priorityMap.medium;
};

const truncateText = (text: string, length: number = 50) => {
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const columns: ColumnDef<ContactSubmission>[] = [
  {
    id: 'select',
    header: ({ table }) => h('input', {
      type: 'checkbox',
      checked: table.getIsAllPageRowsSelected(),
      indeterminate: table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected(),
      onChange: (event: Event) => table.toggleAllPageRowsSelected(!!(event.target as HTMLInputElement).checked),
      class: 'rounded border-gray-300',
      'aria-label': 'Select all',
    }),
    cell: ({ row }) => h('input', {
      type: 'checkbox',
      checked: row.getIsSelected(),
      onChange: (event: Event) => row.toggleSelected(!!(event.target as HTMLInputElement).checked),
      class: 'rounded border-gray-300',
      'aria-label': 'Select row',
    }),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Contact', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const submission = row.original;
      return h('div', { class: 'space-y-1' }, [
        h('p', { class: 'font-medium' }, submission.name),
        h('p', { class: 'text-sm text-muted-foreground' }, submission.email),
        submission.company ? h('p', { class: 'text-xs text-muted-foreground' }, submission.company) : null
      ]);
    },
  },
  {
    accessorKey: 'subject',
    header: 'Subject',
    cell: ({ row }) => {
      const submission = row.original;
      return h('div', [
        h('p', { class: 'font-medium' }, truncateText(submission.subject, 40)),
        h('p', { class: 'text-sm text-muted-foreground' }, truncateText(submission.message, 60))
      ]);
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string;
      const badge = getStatusBadge(status);

      return h(Badge, { variant: badge.variant }, () => [
        h(badge.icon, { class: 'mr-1 h-3 w-3' }),
        badge.text
      ]);
    },
  },
  {
    accessorKey: 'priority',
    header: 'Priority',
    cell: ({ row }) => {
      const priority = row.getValue('priority') as string;
      const badge = getPriorityBadge(priority);

      return h(Badge, { variant: badge.variant }, () => badge.text);
    },
  },
  {
    accessorKey: 'assigned_user',
    header: 'Assigned',
    cell: ({ row }) => {
      const submission = row.original;
      if (submission.assigned_user) {
        return h('div', { class: 'flex items-center gap-2' }, [
          h(UserCheck, { class: 'h-4 w-4 text-green-600' }),
          h('span', { class: 'text-sm' }, submission.assigned_user.name)
        ]);
      }
      return h('span', { class: 'text-sm text-muted-foreground' }, 'Unassigned');
    },
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Submitted', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const date = new Date(row.getValue('created_at'));
      return h('div', date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }));
    },
  },
  {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
      const submission = row.original;

      return h('div', { class: 'flex justify-end' }, [
        h(DropdownMenu, {}, {
          default: () => [
            h(DropdownMenuTrigger, { asChild: true }, {
              default: () => h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, {
                default: () => [
                  h('span', { class: 'sr-only' }, 'Open menu'),
                  h(MoreHorizontal, { class: 'h-4 w-4' })
                ]
              })
            }),
            h(DropdownMenuContent, { align: 'end' }, {
              default: () => [
                h(DropdownMenuItem, {
                  onClick: () => {
                    submissionToManage.value = submission;
                    showManageDrawer.value = true;
                  }
                }, {
                  default: () => [
                    h(Settings, { class: 'mr-2 h-4 w-4' }),
                    'Manage'
                  ]
                }),
                h(DropdownMenuItem, {
                  onClick: () => {
                    submissionToDelete.value = submission;
                    showDeleteModal.value = true;
                  },
                  class: 'text-red-600 focus:text-red-600'
                }, {
                  default: () => [
                    h(Trash2, { class: 'mr-2 h-4 w-4' }),
                    'Delete'
                  ]
                })
              ]
            })
          ]
        })
      ]);
    },
  },
];

const table = useVueTable({
  data: computed(() => props.submissions),
  columns,
  onSortingChange: (updaterOrValue) => {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
  },
  onColumnFiltersChange: (updaterOrValue) => {
    columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue;
  },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  onColumnVisibilityChange: (updaterOrValue) => {
    columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
  },
  onRowSelectionChange: (updaterOrValue) => {
    rowSelection.value = typeof updaterOrValue === 'function' ? updaterOrValue(rowSelection.value) : updaterOrValue;
  },
  onGlobalFilterChange: (updaterOrValue) => {
    globalFilter.value = typeof updaterOrValue === 'function' ? updaterOrValue(globalFilter.value) : updaterOrValue;
  },
  state: computed(() => ({
    sorting: sorting.value,
    columnFilters: columnFilters.value,
    columnVisibility: columnVisibility.value,
    rowSelection: rowSelection.value,
    globalFilter: globalFilter.value,
  })),
});

const selectedSubmissions = computed(() => {
  return table.getFilteredSelectedRowModel().rows.map(row => row.original);
});

// Modal handlers
const handleDeleteConfirm = async () => {
  if (!submissionToDelete.value) return;

  isDeleting.value = true;
  try {
    emit('delete', submissionToDelete.value);
    showDeleteModal.value = false;
    submissionToDelete.value = null;
  } finally {
    isDeleting.value = false;
  }
};

const handleBulkDeleteConfirm = async () => {
  if (submissionsToDelete.value.length === 0) return;

  isDeleting.value = true;
  try {
    emit('bulkDelete', submissionsToDelete.value);
    showBulkDeleteModal.value = false;
    submissionsToDelete.value = [];
  } finally {
    isDeleting.value = false;
  }
};

const handleBulkDelete = () => {
  if (selectedSubmissions.value.length === 0) return;
  submissionsToDelete.value = selectedSubmissions.value;
  showBulkDeleteModal.value = true;
};

const handleExport = () => {
  emit('export');
};

// Drawer handlers
const handleSubmissionUpdated = (updatedSubmission: ContactSubmission) => {
  // Emit event to parent to refresh data
  emit('updated', updatedSubmission);
};
</script>

<template>
  <div class="w-full">
    <div class="flex items-center justify-between py-4">
      <div class="flex items-center gap-2">
        <div class="relative">
          <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search submissions..."
            :model-value="globalFilter"
            @update:model-value="table.setGlobalFilter($event)"
            class="pl-8 max-w-sm"
          />
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="handleExport" variant="outline" size="sm">
          <Download class="mr-2 h-4 w-4" />
          Export Excel
        </Button>
        <Button
          v-if="selectedSubmissions.length > 0"
          @click="handleBulkDelete"
          variant="destructive"
          size="sm"
        >
          Delete Selected ({{ selectedSubmissions.length }})
        </Button>
      </div>
    </div>
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <template v-if="table.getRowModel().rows?.length">
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :data-state="row.getIsSelected() && 'selected'"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
          </template>
          <template v-else>
            <TableRow>
              <TableCell :colspan="columns.length" class="h-24 text-center">
                No results.
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>
    <div class="flex items-center justify-between space-x-2 py-4">
      <div class="flex-1 text-sm text-muted-foreground">
        {{ table.getFilteredSelectedRowModel().rows.length }} of
        {{ table.getFilteredRowModel().rows.length }} row(s) selected.
      </div>
      <div class="space-x-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
        >
          Previous
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
        >
          Next
        </Button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-model:open="showDeleteModal"
      title="Delete Contact Submission"
      :description="`Are you sure you want to delete the contact submission from ${submissionToDelete?.name} (${submissionToDelete?.email})? This action cannot be undone.`"
      confirm-text="Delete Submission"
      cancel-text="Cancel"
      variant="destructive"
      icon="delete"
      :loading="isDeleting"
      @confirm="handleDeleteConfirm"
    />

    <!-- Bulk Delete Confirmation Modal -->
    <ConfirmationModal
      v-model:open="showBulkDeleteModal"
      title="Delete Multiple Contact Submissions"
      :description="`Are you sure you want to delete ${submissionsToDelete.length} contact submissions? This action cannot be undone.`"
      confirm-text="Delete All Selected"
      cancel-text="Cancel"
      variant="destructive"
      icon="delete"
      :loading="isDeleting"
      @confirm="handleBulkDeleteConfirm"
    />

    <!-- Contact Submission Management Drawer -->
    <ContactSubmissionDrawer
      v-model:open="showManageDrawer"
      :submission="submissionToManage"
      :admin-users="adminUsers"
      @updated="handleSubmissionUpdated"
    />
  </div>
</template>
