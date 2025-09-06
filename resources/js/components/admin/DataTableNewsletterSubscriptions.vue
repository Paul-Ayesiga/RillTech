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
import NewsletterBulkEmailDrawer from '@/components/admin/NewsletterBulkEmailDrawer.vue';
import { toast } from 'vue-sonner';
import {
  ArrowUpDown,
  MoreHorizontal,
  Trash2,
  UserCheck,
  UserX,
  Download,
  Search,
  Mail,
  Send
} from 'lucide-vue-next';

interface NewsletterSubscription {
  id: number;
  email: string;
  name: string | null;
  status: 'active' | 'unsubscribed';
  source: string | null;
  subscribed_at: string;
  unsubscribed_at: string | null;
  ip_address: string | null;
}

interface Props {
  subscriptions: NewsletterSubscription[];
  onDelete?: (subscription: NewsletterSubscription) => void;
  onBulkDelete?: (subscriptions: NewsletterSubscription[]) => void;
  onExport?: () => void;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  delete: [subscription: NewsletterSubscription];
  bulkDelete: [subscriptions: NewsletterSubscription[]];
  bulkEmail: [subscriptions: NewsletterSubscription[]];
  export: [];
}>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const globalFilter = ref('');

// Modal states
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const subscriptionToDelete = ref<NewsletterSubscription | null>(null);
const subscriptionsToDelete = ref<NewsletterSubscription[]>([]);
const isDeleting = ref(false);

// Bulk email drawer state
const showBulkEmailDrawer = ref(false);

const columns: ColumnDef<NewsletterSubscription>[] = [
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
    accessorKey: 'email',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('email')),
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => h('div', row.getValue('name') || '-'),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.getValue('status') as string;
      const variant = status === 'active' ? 'default' : 'secondary';
      const icon = status === 'active' ? UserCheck : UserX;
      const text = status === 'active' ? 'Active' : 'Unsubscribed';

      return h(Badge, { variant }, () => [
        h(icon, { class: 'mr-1 h-3 w-3' }),
        text
      ]);
    },
  },
  {
    accessorKey: 'source',
    header: 'Source',
    cell: ({ row }) => h('div', row.getValue('source') || '-'),
  },
  {
    accessorKey: 'subscribed_at',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Subscribed', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const date = new Date(row.getValue('subscribed_at'));
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
      const subscription = row.original;

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
                    subscriptionToDelete.value = subscription;
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
  data: computed(() => props.subscriptions),
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

const selectedSubscriptions = computed(() => {
  return table.getFilteredSelectedRowModel().rows.map(row => row.original);
});

// Modal handlers
const handleDeleteConfirm = async () => {
  if (!subscriptionToDelete.value) return;

  isDeleting.value = true;
  try {
    emit('delete', subscriptionToDelete.value);
    showDeleteModal.value = false;
    subscriptionToDelete.value = null;
  } finally {
    isDeleting.value = false;
  }
};

const handleBulkDeleteConfirm = async () => {
  if (subscriptionsToDelete.value.length === 0) return;

  isDeleting.value = true;
  try {
    emit('bulkDelete', subscriptionsToDelete.value);
    showBulkDeleteModal.value = false;
    subscriptionsToDelete.value = [];
  } finally {
    isDeleting.value = false;
  }
};

const handleBulkDelete = () => {
  if (selectedSubscriptions.value.length === 0) return;
  subscriptionsToDelete.value = selectedSubscriptions.value;
  showBulkDeleteModal.value = true;
};

const handleBulkEmail = () => {
  if (selectedSubscriptions.value.length === 0) return;
  showBulkEmailDrawer.value = true;
};

const handleBulkEmailSent = (count: number) => {
  toast.info(`Newsletter email campaign queued for ${count} subscribers`);
  // Clear selection after sending
  table.toggleAllPageRowsSelected(false);
};

const handleExport = () => {
  emit('export');
};
</script>

<template>
    <div class="w-full">
        <div class="flex items-center justify-between py-4 flex-wrap">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input placeholder="Search subscriptions..." :model-value="globalFilter"
                        @update:model-value="table.setGlobalFilter($event)" class="pl-8 max-w-sm" />
                </div>
            </div>
            <div class="lg:pt-0 flex items-center gap-2 py-4 flex-wrap">
                <Button @click="handleExport" variant="outline" size="sm">
                    <Download class="mr-2 h-4 w-4" />
                    Export Excel
                </Button>
                <Button v-if="selectedSubscriptions.length > 0" @click="handleBulkEmail" variant="default" size="sm">
                    <Send class="mr-2 h-4 w-4" />
                    Send Newsletter ({{ selectedSubscriptions.length }})
                </Button>
                <Button v-if="selectedSubscriptions.length > 0" @click="handleBulkDelete" variant="destructive"
                    size="sm">
                    Delete Selected ({{ selectedSubscriptions.length }})
                </Button>
            </div>
        </div>
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                            :data-state="row.getIsSelected() && 'selected'">
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
                <Button variant="outline" size="sm" :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()">
                    Previous
                </Button>
                <Button variant="outline" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                    Next
                </Button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal v-model:open="showDeleteModal" title="Delete Newsletter Subscription"
            :description="`Are you sure you want to delete the subscription for ${subscriptionToDelete?.email}? This action cannot be undone.`"
            confirm-text="Delete Subscription" cancel-text="Cancel" variant="destructive" icon="delete"
            :loading="isDeleting" @confirm="handleDeleteConfirm" />

        <!-- Bulk Delete Confirmation Modal -->
        <ConfirmationModal v-model:open="showBulkDeleteModal" title="Delete Multiple Subscriptions"
            :description="`Are you sure you want to delete ${subscriptionsToDelete.length} newsletter subscriptions? This action cannot be undone.`"
            confirm-text="Delete All Selected" cancel-text="Cancel" variant="destructive" icon="delete"
            :loading="isDeleting" @confirm="handleBulkDeleteConfirm" />

        <!-- Newsletter Bulk Email Drawer -->
        <NewsletterBulkEmailDrawer v-model:open="showBulkEmailDrawer" :selected-subscriptions="selectedSubscriptions"
            @sent="handleBulkEmailSent" />
    </div>
</template>
