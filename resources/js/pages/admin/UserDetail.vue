<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem, type User, type Role, type Permission } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

// Icons
import { ArrowLeft, User as UserIcon, Mail, Calendar, ShieldCheck, Check, X } from 'lucide-vue-next';

const props = defineProps({
  user: Object as () => User,
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard',
  },
  {
    title: 'User Management',
    href: '/admin/users',
  },
  {
    title: props.user?.name || 'User Details',
    href: `/admin/users/${props.user?.id}`,
  },
];

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
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
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <Head :title="`User: ${user?.name}`" />
    
    <div class="container py-6 px-3">
      <div class="mb-6 flex items-center gap-4">
        <Button as-child variant="outline" size="icon">
          <Link :href="route('admin.users.index')">
            <ArrowLeft class="h-4 w-4" />
          </Link>
        </Button>
        <h1 class="text-2xl font-bold">User Details</h1>
      </div>
      
      <div class="grid gap-6 md:grid-cols-3">
        <!-- User Information -->
        <Card class="md:col-span-1">
          <CardHeader>
            <CardTitle>User Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-col items-center text-center mb-6">
              <div class="h-24 w-24 rounded-full bg-muted flex items-center justify-center mb-4">
                <UserIcon class="h-12 w-12 text-muted-foreground" />
              </div>
              <h3 class="text-xl font-semibold">{{ user.name }}</h3>
              <p class="text-muted-foreground">{{ user.email }}</p>
              
              <div class="flex flex-wrap justify-center gap-1 mt-2">
                <span 
                  v-for="role in user.roles" 
                  :key="role.id" 
                  class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                  :class="getRoleBadgeColor(role.name)"
                >
                  <ShieldCheck v-if="role.name === 'super-admin'" class="mr-1 h-3 w-3" />
                  {{ role.name }}
                </span>
              </div>
            </div>
            
            <div class="space-y-4">
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">ID</span>
                <span class="font-medium">{{ user.id }}</span>
              </div>
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">Email Verified</span>
                <span v-if="user.email_verified_at" class="text-green-600 dark:text-green-400 font-medium">
                  <Check class="inline h-4 w-4 mr-1" />
                  {{ formatDate(user.email_verified_at) }}
                </span>
                <span v-else class="text-amber-600 dark:text-amber-400 font-medium">
                  <X class="inline h-4 w-4 mr-1" />
                  Not verified
                </span>
              </div>
              <div class="flex justify-between border-b pb-2">
                <span class="text-muted-foreground">Created</span>
                <span class="font-medium">{{ formatDate(user.created_at) }}</span>
              </div>
              <div class="flex justify-between pb-2">
                <span class="text-muted-foreground">Last Updated</span>
                <span class="font-medium">{{ formatDate(user.updated_at) }}</span>
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <div class="flex w-full gap-2">
              <Button as-child variant="outline" class="flex-1">
                <Link :href="route('admin.users.edit', user.id)" class="flex items-center justify-center">
                  Edit User
                </Link>
              </Button>
              <Button as-child variant="default" class="flex-1">
                <Link :href="`mailto:${user.email}`" class="flex items-center justify-center">
                  <Mail class="mr-2 h-4 w-4" />
                  Email
                </Link>
              </Button>
            </div>
          </CardFooter>
        </Card>
        
        <!-- Roles and Permissions -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle>Roles & Permissions</CardTitle>
            <CardDescription>
              User's assigned roles and permissions
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Tabs defaultValue="roles" class="w-full">
              <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="roles">Roles</TabsTrigger>
                <TabsTrigger value="permissions">Permissions</TabsTrigger>
              </TabsList>
              
              <!-- Roles Tab -->
              <TabsContent value="roles">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Role</TableHead>
                      <TableHead>Guard</TableHead>
                      <TableHead>Permissions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-if="!user.roles || user.roles.length === 0">
                      <TableCell colspan="3" class="h-24 text-center">
                        No roles assigned.
                      </TableCell>
                    </TableRow>
                    <TableRow v-for="role in user.roles" :key="role.id">
                      <TableCell>
                        <div class="flex items-center gap-2">
                          <ShieldCheck v-if="role.name === 'super-admin'" class="h-4 w-4 text-primary" />
                          <span class="font-medium">{{ role.name }}</span>
                        </div>
                      </TableCell>
                      <TableCell>{{ role.guard_name }}</TableCell>
                      <TableCell>
                        <span class="text-muted-foreground">
                          {{ role.permissions ? role.permissions.length : 0 }} permissions
                        </span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </TabsContent>
              
              <!-- Permissions Tab -->
              <TabsContent value="permissions">
                <div class="space-y-4">
                  <div v-if="!user.roles || user.roles.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No permissions available. Assign roles to this user first.</p>
                  </div>
                  
                  <div v-for="role in user.roles" :key="role.id" class="space-y-2">
                    <h3 class="font-medium flex items-center gap-2">
                      <ShieldCheck v-if="role.name === 'super-admin'" class="h-4 w-4 text-primary" />
                      {{ role.name }} Role Permissions
                    </h3>
                    
                    <div v-if="!role.permissions || role.permissions.length === 0" class="text-muted-foreground text-sm">
                      No permissions for this role.
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                      <div 
                        v-for="permission in role.permissions" 
                        :key="permission.id"
                        class="rounded-md border p-2 text-sm"
                      >
                        {{ permission.name }}
                      </div>
                    </div>
                  </div>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
