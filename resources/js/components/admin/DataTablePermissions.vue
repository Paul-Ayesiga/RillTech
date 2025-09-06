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
import { ArrowUpDown, ChevronDown, Pencil, Trash2, FileSpreadsheet, FileText, UsersRound, FolderInput, LoaderCircle } from 'lucide-vue-next'
import { h, ref, computed } from 'vue'
import { valueUpdater } from '@/utils'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
  DropdownMenuSeparator,
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
import { type Permission, type PermissionGroup, type Role } from '@/types'

const props = defineProps<{
  permissions: Permission[]
  permissionGroups?: PermissionGroup[]
  roles?: Role[]
}>()

const emit = defineEmits<{
  (e: 'edit', permission: Permission): void
  (e: 'delete', permission: Permission): void
  (e: 'bulk-delete', ids: number[]): void
  (e: 'bulk-assign-group', ids: number[], groupId: number | null): void
  (e: 'bulk-assign-roles', ids: number[], roleIds: number[]): void
  (e: 'open-bulk-assign-group-dialog'): void
  (e: 'open-bulk-assign-roles-dialog'): void
}>()

// Define columns
const columns: ColumnDef<Permission>[] = [
  {
    id: 'select',
    header: ({ table }) =>
      h('div', { class: 'px-1' }, [
        h('input', {
          type: 'checkbox',
          class: 'h-4 w-4 rounded border-border text-primary focus:ring-primary',
          checked: table.getIsAllRowsSelected(),
          indeterminate: table.getIsSomeRowsSelected(),
          onChange: table.getToggleAllRowsSelectedHandler(),
        })
      ]),
    cell: ({ row }) =>
      h('div', { class: 'px-1' }, [
        h('input', {
          type: 'checkbox',
          class: 'h-4 w-4 rounded border-border text-primary focus:ring-primary',
          checked: row.getIsSelected(),
          disabled: false,
          onChange: row.getToggleSelectedHandler(),
        })
      ]),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
  },
  {
    accessorKey: 'guard_name',
    header: 'Guard',
    cell: ({ row }) => h('div', {}, row.getValue('guard_name')),
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, 'Actions'),
    cell: ({ row }) => {
      const permission = row.original

      return h('div', { class: 'flex justify-end gap-1' }, [
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('edit', permission),
          title: 'Edit Permission'
        }, () => h(Pencil, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('delete', permission),
          title: 'Delete Permission'
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

// Loading states
const isExporting = ref(false)
const isDeleting = ref(false)
const isAssigningGroup = ref(false)
const isAssigningRoles = ref(false)

// Selected group for bulk assignment
const selectedGroupId = ref<number | null>(null)

// Selected roles for bulk assignment
const selectedRoleIds = ref<number[]>([])

const table = useVueTable({
  get data() { return props.permissions },
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

// Get selected permission IDs
const selectedPermissionIds = computed(() => {
  const selectedRows = table.getSelectedRowModel().rows
  return selectedRows.map(row => row.original.id)
})

// Check if any permissions are selected
const hasSelections = computed(() => selectedPermissionIds.value.length > 0)

// Bulk delete permissions
const bulkDelete = () => {
  if (!hasSelections.value) return

  const ids = table.getSelectedRowModel().rows.map(row => row.original.id)
  console.log('Bulk delete with direct IDs:', ids)
  isDeleting.value = true
  emit('bulk-delete', ids)
}

// Bulk assign permissions to group
const bulkAssignGroup = () => {
  if (!hasSelections.value) return

  const ids = table.getSelectedRowModel().rows.map(row => row.original.id)
  console.log('Bulk assign group with direct IDs:', ids)
  isAssigningGroup.value = true
  emit('bulk-assign-group', ids, selectedGroupId.value)
}

// Bulk assign permissions to roles
const bulkAssignRoles = () => {
  if (!hasSelections.value || selectedRoleIds.value.length === 0) return

  const ids = table.getSelectedRowModel().rows.map(row => row.original.id)
  console.log('Bulk assign roles with direct IDs:', ids)
  isAssigningRoles.value = true
  emit('bulk-assign-roles', ids, selectedRoleIds.value)
}

// Export selected permissions to Excel
const exportExcel = () => {
  if (!hasSelections.value) return

  const ids = table.getSelectedRowModel().rows.map(row => row.original.id)
  console.log('Export Excel with direct IDs:', ids)
  isExporting.value = true
  const idsString = ids.join(',')
  window.location.href = `/admin/permissions/export-excel?ids=${idsString}`

  // Reset loading state after a delay
  setTimeout(() => {
    isExporting.value = false
  }, 1000)
}

// Export selected permissions to PDF
const exportPdf = () => {
  if (!hasSelections.value) return

  const ids = table.getSelectedRowModel().rows.map(row => row.original.id)
  console.log('Export PDF with direct IDs:', ids)
  isExporting.value = true
  const idsString = ids.join(',')
  window.location.href = `/admin/permissions/export-pdf?ids=${idsString}`

  // Reset loading state after a delay
  setTimeout(() => {
    isExporting.value = false
  }, 1000)
}
</script>

<template>
  <div class="w-full">
    <div class="flex flex-col sm:flex-row sm:items-center py-4 gap-2">
      <div class="flex items-center gap-2">
        <Input
          class="max-w-sm"
          placeholder="Filter permissions..."
          :model-value="table.getColumn('name')?.getFilterValue() as string"
          @update:model-value="table.getColumn('name')?.setFilterValue($event)"
        />

        <!-- Bulk Actions Dropdown -->
        <DropdownMenu v-if="hasSelections">
          <DropdownMenuTrigger as-child>
            <Button variant="outline">
              Bulk Actions <ChevronDown class="ml-2 h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="start" class="w-56">
            <DropdownMenuItem @click="bulkDelete" :disabled="isDeleting">
              <Trash2 class="mr-2 h-4 w-4" />
              <span>Delete Selected</span>
              <LoaderCircle v-if="isDeleting" class="ml-2 h-4 w-4 animate-spin" />
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem @click="() => {
              const ids = table.getSelectedRowModel().rows.map(row => row.original.id);
              console.log('DataTablePermissions emitting direct IDs:', ids);
              $emit('open-bulk-assign-group-dialog', ids);
            }">
              <FolderInput class="mr-2 h-4 w-4" />
              <span>Assign to Group</span>
            </DropdownMenuItem>

            <DropdownMenuItem @click="() => {
              const ids = table.getSelectedRowModel().rows.map(row => row.original.id);
              console.log('DataTablePermissions emitting direct IDs for roles:', ids);
              $emit('open-bulk-assign-roles-dialog', ids);
            }">
              <UsersRound class="mr-2 h-4 w-4" />
              <span>Assign to Roles</span>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem @click="exportExcel" :disabled="isExporting">
              <FileSpreadsheet class="mr-2 h-4 w-4" />
              <span>Export Excel</span>
              <LoaderCircle v-if="isExporting" class="ml-2 h-4 w-4 animate-spin" />
            </DropdownMenuItem>

            <DropdownMenuItem @click="exportPdf" :disabled="isExporting">
              <FileText class="mr-2 h-4 w-4" />
              <span>Export PDF</span>
              <LoaderCircle v-if="isExporting" class="ml-2 h-4 w-4 animate-spin" />
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <div class="flex items-center ml-auto">
        <div v-if="hasSelections" class="text-sm text-muted-foreground mr-4">
          {{ Object.keys(table.getState().rowSelection).length }} selected
        </div>

        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline">
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
              No permissions found.
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
