<script setup lang="ts">
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown, Pencil, Trash2 } from 'lucide-vue-next'
import { h, ref } from 'vue'
import { valueUpdater } from '@/utils'

import { Button } from '@/components/ui/button'
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
import { type Permission } from '@/types'

const props = defineProps<{
  permissions: Permission[];
  groupName?: string;
  groupColor?: string | null;
}>();

const emit = defineEmits<{
  (e: 'edit', permission: Permission): void;
  (e: 'delete', permission: Permission): void;
}>();

// Define columns
const columns: ColumnDef<Permission>[] = [
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
    id: 'roles',
    header: 'Roles',
    cell: ({ row }) => {
      const permission = row.original;

      // No need for debug logging in production

      // Check if roles exist and have length
      if (!permission.roles || !Array.isArray(permission.roles) || permission.roles.length === 0) {
        return h('span', { class: 'text-muted-foreground text-sm' }, 'No roles assigned');
      }

      return h('div', { class: 'flex flex-wrap gap-1' },
        permission.roles.map(role =>
          h(Badge, {
            key: role.id,
            variant: 'outline',
            class: 'mr-1 mb-1'
          }, () => role.name)
        )
      );
    },
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, 'Actions'),
    cell: ({ row }) => {
      const permission = row.original;

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
      ]);
    },
    enableSorting: false,
    enableHiding: false,
  },
];

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});

const table = useVueTable({
  get data() { return props.permissions },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
  onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
  onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get columnVisibility() { return columnVisibility.value },
  },
});
</script>

<template>
  <div class="space-y-2">
    <!-- Group Header -->
    <div v-if="groupName" class="flex items-center gap-2 px-2 py-1 rounded-md"
      :style="{ backgroundColor: groupColor ? `${groupColor}20` : 'var(--muted)' }">
      <div v-if="groupColor" class="w-3 h-3 rounded-full" :style="{ backgroundColor: groupColor }"></div>
      <h3 class="text-lg font-semibold">{{ groupName }}</h3>
      <Badge variant="outline">
        {{ permissions.length }}
      </Badge>
    </div>

    <div class="w-full">
      <div class="flex items-center py-4">
        <Input
          class="max-w-sm"
          placeholder="Filter permissions..."
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
                No permissions found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-end space-x-2 py-4">
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
    </div>
  </div>
</template>
