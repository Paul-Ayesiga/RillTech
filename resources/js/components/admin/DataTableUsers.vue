<script setup lang="ts">
import type {
  ColumnDef,
  ColumnFiltersState,
  ExpandedState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown, Pencil, Trash2, ShieldCheck, User as UserIcon, Check, X } from 'lucide-vue-next'
import { h, ref } from 'vue'
import { valueUpdater } from '@/utils'

import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { type User, type Role } from '@/types'

const props = defineProps<{
  users: User[]
}>()

const emit = defineEmits<{
  (e: 'edit', user: User): void
  (e: 'delete', user: User): void
  (e: 'view', user: User): void
}>()

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Get role badge color
const getRoleBadgeColor = (roleName: string) => {
  switch (roleName) {
    case 'super-admin':
      return 'bg-primary/10 text-primary border-primary/20';
    case 'admin':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800/30';
    case 'client':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800/30';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800/30';
  }
};

// Define columns
const columns: ColumnDef<User>[] = [
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const user = row.original
      
      return h('div', { class: 'flex items-center gap-2' }, [
        h(UserIcon, { class: 'h-4 w-4 text-muted-foreground' }),
        h('div', {}, [
          h('p', { class: 'font-medium' }, user.name),
          user.email_verified_at 
            ? h('p', { class: 'text-xs text-green-600 dark:text-green-400' }, 'Verified')
            : h('p', { class: 'text-xs text-amber-600 dark:text-amber-400' }, 'Unverified')
        ])
      ])
    },
  },
  {
    accessorKey: 'email',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-mono text-xs' }, row.getValue('email')),
  },
  {
    id: 'roles',
    header: 'Roles',
    cell: ({ row }) => {
      const user = row.original
      
      if (!user.roles || user.roles.length === 0) {
        return h('span', { class: 'text-muted-foreground text-sm' }, 'No roles assigned')
      }

      return h('div', { class: 'flex flex-wrap gap-1' },
        user.roles.map(role =>
          h('span', {
            key: role.id,
            class: `inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${getRoleBadgeColor(role.name)}`
          }, [
            role.name === 'super-admin' ? h(ShieldCheck, { class: 'mr-1 h-3 w-3' }) : null,
            role.name
          ])
        )
      )
    },
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Created', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', {}, formatDate(row.getValue('created_at'))),
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, 'Actions'),
    cell: ({ row }) => {
      const user = row.original
      const currentUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content')
      const isSelf = user.id.toString() === currentUserId

      return h('div', { class: 'flex justify-end gap-1' }, [
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('view', user),
          title: 'View User'
        }, () => h('svg', { 
          xmlns: 'http://www.w3.org/2000/svg', 
          width: '16', 
          height: '16', 
          viewBox: '0 0 24 24', 
          fill: 'none', 
          stroke: 'currentColor', 
          'stroke-width': '2', 
          'stroke-linecap': 'round', 
          'stroke-linejoin': 'round',
          class: 'h-4 w-4'
        }, [
          h('path', { d: 'M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z' }),
          h('circle', { cx: '12', cy: '12', r: '3' })
        ])),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('edit', user),
          title: 'Edit User'
        }, () => h(Pencil, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('delete', user),
          disabled: isSelf,
          title: isSelf ? 'Cannot delete your own account' : 'Delete User'
        }, () => h(Trash2, { class: 'h-4 w-4' }))
      ])
    },
    enableSorting: false,
    enableHiding: false,
  },
]

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const table = useVueTable({
  get data() { return props.users },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getExpandedRowModel: getExpandedRowModel(),
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
  onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
  initialState: {
    pagination: {
      pageSize: 10,
    },
  },
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
    get expanded() { return expanded.value },
  },
})
</script>

<template>
  <div class="w-full">
    <div class="flex items-center py-4">
      <Input
        class="max-w-sm"
        placeholder="Filter users..."
        :model-value="table.getColumn('name')?.getFilterValue() as string"
        @update:model-value="table.getColumn('name')?.setFilterValue($event)"
      />
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="outline" class="ml-auto">
            Columns <ChevronDown class="ml-2 h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuCheckboxItem
            v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
            :key="column.id"
            class="capitalize"
            :model-value="column.getIsVisible()"
            @update:model-value="(value) => {
              column.toggleVisibility(!!value)
            }"
          >
            {{ column.id }}
          </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
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

          <TableRow v-else>
            <TableCell
              :colspan="columns.length"
              class="h-24 text-center"
            >
              No users found.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-between py-4">
      <!-- Pagination Information -->
      <div class="text-sm text-muted-foreground">
        Showing {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}
        to {{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, table.getFilteredRowModel().rows.length) }}
        of {{ table.getFilteredRowModel().rows.length }} entries
      </div>

      <!-- Pagination Controls -->
      <div class="flex items-center space-x-6">
        <div class="flex items-center space-x-2">
          <span class="text-sm font-medium">Rows per page</span>
          <select
            class="h-8 w-16 rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            v-model="table.getState().pagination.pageSize"
            @change="table.setPageSize(Number($event.target.value))"
          >
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>

        <div class="flex items-center space-x-2">
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
    </div>
  </div>
</template>
