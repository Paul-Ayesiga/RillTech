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
import { ArrowUpDown, ChevronDown, Pencil, Trash2, ShieldCheck } from 'lucide-vue-next'
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
import { type Role } from '@/types'

const props = defineProps<{
  roles: Role[]
}>()

const emit = defineEmits<{
  (e: 'edit', role: Role): void
  (e: 'delete', role: Role): void
}>()

// Define columns
const columns: ColumnDef<Role>[] = [
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const role = row.original
      const isAdmin = role.name === 'super-admin'

      return h('div', { class: 'flex items-center gap-2' }, [
        isAdmin ? h(ShieldCheck, { class: 'h-4 w-4 text-primary' }) : null,
        h('span', { class: 'font-medium' }, role.name)
      ])
    },
  },
  {
    accessorKey: 'guard_name',
    header: 'Guard',
    cell: ({ row }) => h('div', {}, row.getValue('guard_name')),
  },
  {
    id: 'permissions',
    header: 'Permissions',
    cell: ({ row }) => {
      const role = row.original
      const isAdmin = role.name === 'super-admin'

      if (isAdmin) {
        return h('div', {}, [
          h(Badge, { variant: 'default', class: 'mr-1 mb-1' }, () => 'All Permissions')
        ])
      }

      if (!role.permissions || role.permissions.length === 0) {
        return h('span', { class: 'text-muted-foreground text-sm' }, 'No permissions assigned')
      }

      return h('div', { class: 'flex flex-wrap gap-1 max-w-[500px]' },
        role.permissions.map(permission =>
          h(Badge, {
            key: permission.id,
            variant: 'outline',
            class: 'mr-1 mb-1'
          }, () => permission.name)
        )
      )
    },
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, 'Actions'),
    cell: ({ row }) => {
      const role = row.original
      const isAdmin = role.name === 'super-admin'

      return h('div', { class: 'flex justify-end gap-1' }, [
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('edit', role),
          title: 'Edit Role'
        }, () => h(Pencil, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => emit('delete', role),
          disabled: isAdmin,
          title: 'Delete Role'
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
  get data() { return props.roles },
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
        placeholder="Filter roles..."
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
              :class="{'bg-muted/40': row.original.name === 'super-admin'}"
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
              No roles found.
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
