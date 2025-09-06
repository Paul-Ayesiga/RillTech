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
import { ArrowUpDown, ChevronDown, Pencil, Trash2, ShieldCheck, User as UserIcon, Check, X, LoaderCircle, Download, FileText, Users, Mail, MoreVertical, Eye, UserX, Ban, UserCheck } from 'lucide-vue-next'
import { h, ref, computed } from 'vue'
import { valueUpdater } from '@/utils'

import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
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
import { Badge } from '@/components/ui/badge'
import { type User, type Role } from '@/types'

const props = defineProps<{
  users: User[]
}>()

const emit = defineEmits<{
  (e: 'edit', user: User): void
  (e: 'delete', user: User): void
  (e: 'view', user: User): void
  (e: 'suspend', user: User): void
  (e: 'ban', user: User): void
  (e: 'activate', user: User): void
  (e: 'bulk-delete', ids: number[]): void
  (e: 'bulk-assign-roles', ids: number[], roleIds: number[]): void
  (e: 'bulk-email', ids: number[]): void
  (e: 'open-bulk-assign-roles-dialog', ids: number[]): void
  (e: 'export-excel', ids: number[]): void
  (e: 'export-pdf', ids: number[]): void
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

// Bulk action states
const selectedUserIds = ref<number[]>([])

// Selection functions
const isAllSelected = computed(() => {
  return props.users.length > 0 && selectedUserIds.value.length === props.users.length
})

const isIndeterminate = computed(() => {
  return selectedUserIds.value.length > 0 && selectedUserIds.value.length < props.users.length
})

const hasSelections = computed(() => selectedUserIds.value.length > 0)

const toggleAllUsers = (checked: boolean) => {
  if (checked) {
    selectedUserIds.value = props.users.map(user => user.id)
  } else {
    selectedUserIds.value = []
  }
}

const toggleUser = (userId: number, checked: boolean) => {
  if (checked) {
    if (!selectedUserIds.value.includes(userId)) {
      selectedUserIds.value.push(userId)
    }
  } else {
    const index = selectedUserIds.value.indexOf(userId)
    if (index > -1) {
      selectedUserIds.value.splice(index, 1)
    }
  }
}

const isUserSelected = (userId: number) => {
  return selectedUserIds.value.includes(userId)
}

// Define columns
const columns: ColumnDef<User>[] = [
  {
    id: 'select',
    header: () => h('div', { class: 'flex items-center' }, [
      h('input', {
        type: 'checkbox',
        checked: isAllSelected.value,
        indeterminate: isIndeterminate.value,
        onChange: (e: Event) => toggleAllUsers((e.target as HTMLInputElement).checked),
        class: 'h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded'
      })
    ]),
    cell: ({ row }) => {
      const user = row.original
      return h('div', { class: 'flex items-center' }, [
        h('input', {
          type: 'checkbox',
          checked: isUserSelected(user.id),
          onChange: (e: Event) => toggleUser(user.id, (e.target as HTMLInputElement).checked),
          class: 'h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded'
        })
      ])
    },
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
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const user = row.original
      const status = user.status || 'active'

      const getStatusBadge = (status: string) => {
        switch (status) {
          case 'active':
            return {
              class: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800/30',
              icon: h(UserCheck, { class: 'mr-1 h-3 w-3' }),
              text: 'Active'
            }
          case 'suspended':
            return {
              class: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800/30',
              icon: h(UserX, { class: 'mr-1 h-3 w-3' }),
              text: 'Suspended'
            }
          case 'banned':
            return {
              class: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/30',
              icon: h(Ban, { class: 'mr-1 h-3 w-3' }),
              text: 'Banned'
            }
          default:
            return {
              class: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800/30',
              icon: h(UserIcon, { class: 'mr-1 h-3 w-3' }),
              text: 'Unknown'
            }
        }
      }

      const badge = getStatusBadge(status)

      return h('span', {
        class: `inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${badge.class}`
      }, [
        badge.icon,
        badge.text
      ])
    },
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
      const isSuperAdmin = user.roles && user.roles.some((role: any) => role.name === 'super-admin')

      return h('div', { class: 'flex justify-end' }, [
        h(DropdownMenu, {}, {
          default: () => [
            h(DropdownMenuTrigger, { asChild: true }, {
              default: () => h(Button, {
                variant: 'ghost',
                size: 'icon',
                title: 'User Actions'
              }, () => h(MoreVertical, { class: 'h-4 w-4' }))
            }),
            h(DropdownMenuContent, { align: 'end', class: 'w-48' }, {
              default: () => [
                // View User
                h(DropdownMenuItem, {
                  onClick: () => emit('view', user)
                }, {
                  default: () => [
                    h(Eye, { class: 'mr-2 h-4 w-4' }),
                    'View User'
                  ]
                }),

                // Edit User
                h(DropdownMenuItem, {
                  onClick: () => emit('edit', user)
                }, {
                  default: () => [
                    h(Pencil, { class: 'mr-2 h-4 w-4' }),
                    'Edit User'
                  ]
                }),

                h(DropdownMenuSeparator),

                // Account Status Actions (exclude super-admins)
                ...(user.status === 'active' && !isSuperAdmin ? [
                  h(DropdownMenuItem, {
                    onClick: () => emit('suspend', user),
                    class: 'text-orange-600 focus:text-orange-600'
                  }, {
                    default: () => [
                      h(UserX, { class: 'mr-2 h-4 w-4' }),
                      'Suspend Account'
                    ]
                  }),
                  h(DropdownMenuItem, {
                    onClick: () => emit('ban', user),
                    class: 'text-red-600 focus:text-red-600'
                  }, {
                    default: () => [
                      h(Ban, { class: 'mr-2 h-4 w-4' }),
                      'Ban Account'
                    ]
                  })
                ] : []),

                ...(user.status === 'suspended' ? [
                  h(DropdownMenuItem, {
                    onClick: () => emit('activate', user),
                    class: 'text-green-600 focus:text-green-600'
                  }, {
                    default: () => [
                      h(UserCheck, { class: 'mr-2 h-4 w-4' }),
                      'Activate Account'
                    ]
                  }),
                  // Only show ban option for non-super-admins
                  ...(!isSuperAdmin ? [
                    h(DropdownMenuItem, {
                      onClick: () => emit('ban', user),
                      class: 'text-red-600 focus:text-red-600'
                    }, {
                      default: () => [
                        h(Ban, { class: 'mr-2 h-4 w-4' }),
                        'Ban Account'
                      ]
                    })
                  ] : [])
                ] : []),

                ...(user.status === 'banned' ? [
                  h(DropdownMenuItem, {
                    onClick: () => emit('activate', user),
                    class: 'text-green-600 focus:text-green-600'
                  }, {
                    default: () => [
                      h(UserCheck, { class: 'mr-2 h-4 w-4' }),
                      'Activate Account'
                    ]
                  })
                ] : []),

                h(DropdownMenuSeparator),

                // Delete User (only if not self and not super-admin)
                ...(!isSelf && !isSuperAdmin ? [
                  h(DropdownMenuItem, {
                    onClick: () => emit('delete', user),
                    class: 'text-red-600 focus:text-red-600'
                  }, {
                    default: () => [
                      h(Trash2, { class: 'mr-2 h-4 w-4' }),
                      'Delete User'
                    ]
                  })
                ] : [])
              ]
            })
          ]
        })
      ])
    },
    enableSorting: false,
    enableHiding: false,
  },
]

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
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
    get expanded() { return expanded.value },
  },
})

// Bulk delete users
const bulkDelete = () => {
  if (!hasSelections.value) return

  console.log('Bulk delete users with IDs:', selectedUserIds.value)
  emit('bulk-delete', selectedUserIds.value)
}

// Bulk assign roles to users
const bulkAssignRoles = () => {
  if (!hasSelections.value) return

  console.log('Bulk assign roles with IDs:', selectedUserIds.value)
  emit('bulk-assign-roles', selectedUserIds.value, [])
}

// Bulk email users
const bulkEmail = () => {
  if (!hasSelections.value) return

  console.log('Bulk email users with IDs:', selectedUserIds.value)
  emit('bulk-email', selectedUserIds.value)
}

// Export selected users to Excel
const exportExcel = () => {
  if (!hasSelections.value) return

  console.log('Export Excel with IDs:', selectedUserIds.value)
  emit('export-excel', selectedUserIds.value)
}

// Export selected users to PDF
const exportPdf = () => {
  if (!hasSelections.value) return

  console.log('Export PDF with IDs:', selectedUserIds.value)
  emit('export-pdf', selectedUserIds.value)
}
</script>

<template>
  <div class="w-full">
    <div class="flex items-center py-4">
      <Input
        class="max-w-sm"
        placeholder="Filter users..."
        :model-value="(table.getColumn('name')?.getFilterValue() as string) ?? ''"
        @update:model-value="table.getColumn('name')?.setFilterValue($event)"
      />

      <!-- Bulk Actions Dropdown -->
      <DropdownMenu v-if="hasSelections">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" class="ml-2">
            Bulk Actions <ChevronDown class="ml-2 h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-56">
          <DropdownMenuItem @click="bulkDelete">
            <Trash2 class="mr-2 h-4 w-4" />
            <span>Delete Selected</span>
          </DropdownMenuItem>

          <DropdownMenuSeparator />

          <DropdownMenuItem @click="() => {
            $emit('open-bulk-assign-roles-dialog', selectedUserIds);
          }">
            <Users class="mr-2 h-4 w-4" />
            <span>Assign Roles</span>
          </DropdownMenuItem>

          <DropdownMenuItem @click="bulkEmail">
            <Mail class="mr-2 h-4 w-4" />
            <span>Email Selected</span>
          </DropdownMenuItem>

          <DropdownMenuSeparator />

          <DropdownMenuItem @click="exportExcel">
            <FileText class="mr-2 h-4 w-4" />
            <span>Export to Excel</span>
          </DropdownMenuItem>

          <DropdownMenuItem @click="exportPdf">
            <Download class="mr-2 h-4 w-4" />
            <span>Export to PDF</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

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
              :class="{ 'bg-muted/50': isUserSelected(row.original.id) }"
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
