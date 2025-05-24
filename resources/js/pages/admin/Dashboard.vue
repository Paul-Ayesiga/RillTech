<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    Mail,
    MessageSquare,
    Activity,
    TrendingUp,
    TrendingDown,
    Clock,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Shield,
    Key,
    CreditCard,
    Eye
} from 'lucide-vue-next';
import { useEcho } from "@laravel/echo-vue";

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface SystemStats {
    users: {
        total: number;
        active: number;
        suspended: number;
        banned: number;
        today: number;
        this_week: number;
        this_month: number;
    };
    newsletter: {
        total: number;
        active: number;
        unsubscribed: number;
        today: number;
        this_week: number;
        this_month: number;
    };
    contacts: {
        total: number;
        new: number;
        in_progress: number;
        resolved: number;
        urgent: number;
        today: number;
        this_week: number;
    };
    system: {
        roles: number;
        permissions: number;
        stripe_customers: number;
    };
}

interface ActivityLog {
    id: number;
    description: string;
    log_name: string;
    event: string;
    causer: {
        id: number;
        name: string;
        email: string;
    } | null;
    subject_type: string;
    subject_id: number;
    properties: any;
    created_at: string;
    created_at_human: string;
    category: string;
    icon: string;
    color: string;
}

interface TrendData {
    date: string;
    count: number;
}

interface RecentUser {
    id: number;
    name: string;
    email: string;
    status: string;
    roles: string[];
    created_at: string;
    created_at_human: string;
}

interface RecentNewsletter {
    id: number;
    email: string;
    name: string;
    status: string;
    created_at: string;
    created_at_human: string;
}

interface RecentContact {
    id: number;
    name: string;
    email: string;
    subject: string;
    status: string;
    priority: string;
    assigned_user: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    created_at_human: string;
}

const props = defineProps<{
    systemStats: SystemStats;
    recentActivities: ActivityLog[];
    trends: {
        users: TrendData[];
        newsletter: TrendData[];
        contacts: TrendData[];
    };
    recentData: {
        users: RecentUser[];
        newsletter: RecentNewsletter[];
        contacts: RecentContact[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: 'admin/dashboard',
    },
];

// Listen for userCreated events
useEcho<{ user: User }>('user', "userCreated", (e: { user: User }) => {
    console.log('New user created:', e.user);
});

// Helper functions
const getStatusColor = (status: string) => {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'suspended': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'banned': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'new': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'in_progress': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'resolved': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'unsubscribed': return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    }
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'urgent': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'high': return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
        case 'medium': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'low': return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    }
};

const getActivityIcon = (event: string) => {
    switch (event) {
        case 'created': return CheckCircle;
        case 'updated': return Activity;
        case 'deleted': return XCircle;
        default: return Activity;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Welcome Section -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
                <p class="text-muted-foreground">Welcome back! Here's what's happening with your system.</p>
            </div>

            <!-- System Overview Cards -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Users Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ systemStats.users.total }}</div>
                        <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                            <span class="flex items-center">
                                <TrendingUp class="mr-1 h-3 w-3 text-green-500" />
                                {{ systemStats.users.today }} today
                            </span>
                            <span>{{ systemStats.users.this_week }} this week</span>
                        </div>
                        <div class="mt-2 flex space-x-1">
                            <Badge variant="secondary" class="text-xs">{{ systemStats.users.active }} active</Badge>
                            <Badge variant="outline" class="text-xs" v-if="systemStats.users.suspended > 0">{{ systemStats.users.suspended }} suspended</Badge>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="ghost" class="w-full">
                            <Link :href="route('admin.users.index')">Manage Users</Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Newsletter Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Newsletter Subscribers</CardTitle>
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ systemStats.newsletter.total }}</div>
                        <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                            <span class="flex items-center">
                                <TrendingUp class="mr-1 h-3 w-3 text-green-500" />
                                {{ systemStats.newsletter.today }} today
                            </span>
                            <span>{{ systemStats.newsletter.this_week }} this week</span>
                        </div>
                        <div class="mt-2 flex space-x-1">
                            <Badge variant="secondary" class="text-xs">{{ systemStats.newsletter.active }} active</Badge>
                            <Badge variant="outline" class="text-xs" v-if="systemStats.newsletter.unsubscribed > 0">{{ systemStats.newsletter.unsubscribed }} unsubscribed</Badge>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="ghost" class="w-full">
                            <Link :href="route('admin.newsletter-subscriptions.index')">Manage Subscriptions</Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Contact Submissions Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Contact Submissions</CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ systemStats.contacts.total }}</div>
                        <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                            <span class="flex items-center">
                                <TrendingUp class="mr-1 h-3 w-3 text-green-500" />
                                {{ systemStats.contacts.today }} today
                            </span>
                            <span>{{ systemStats.contacts.this_week }} this week</span>
                        </div>
                        <div class="mt-2 flex space-x-1">
                            <Badge variant="destructive" class="text-xs" v-if="systemStats.contacts.urgent > 0">{{ systemStats.contacts.urgent }} urgent</Badge>
                            <Badge variant="secondary" class="text-xs">{{ systemStats.contacts.new }} new</Badge>
                            <Badge variant="outline" class="text-xs">{{ systemStats.contacts.in_progress }} in progress</Badge>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="ghost" class="w-full">
                            <Link :href="route('admin.contact-submissions.index')">Manage Contacts</Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- System Info Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">System Info</CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Roles</span>
                                <span class="font-medium">{{ systemStats.system.roles }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Permissions</span>
                                <span class="font-medium">{{ systemStats.system.permissions }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Stripe Customers</span>
                                <span class="font-medium">{{ systemStats.system.stripe_customers }}</span>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="ghost" class="w-full">
                            <Link :href="route('admin.roles-permissions')">Manage Roles</Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Activity Logs and Recent Data -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Activity Logs -->
                <Card class="lg:col-span-1" data-activity-logs>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Recent Activity
                        </CardTitle>
                        <CardDescription>Latest system activities and changes</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start space-x-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
                                        <Activity class="h-4 w-4" :class="activity.color" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium leading-tight">{{ activity.description }}</p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <Badge variant="secondary" class="text-xs">{{ activity.category }}</Badge>
                                        <span v-if="activity.causer" class="text-xs text-muted-foreground">
                                            by {{ activity.causer.name }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1">{{ activity.created_at_human }}</p>
                                </div>
                            </div>
                            <div v-if="recentActivities.length === 0" class="text-center py-8 text-muted-foreground">
                                <Activity class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>No recent activities</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Users -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Recent Users
                        </CardTitle>
                        <CardDescription>Latest user registrations</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <div v-for="user in recentData.users" :key="user.id" class="flex items-center justify-between p-3 rounded-lg border">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <Badge :class="getStatusColor(user.status)" class="text-xs">{{ user.status }}</Badge>
                                        <span v-if="user.roles.length > 0" class="text-xs text-muted-foreground">
                                            {{ user.roles.join(', ') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">{{ user.created_at_human }}</p>
                                    <Button as-child variant="ghost" size="sm">
                                        <Link :href="route('admin.users.show', user.id)">
                                            <Eye class="h-3 w-3" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                            <div v-if="recentData.users.length === 0" class="text-center py-8 text-muted-foreground">
                                <Users class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>No recent users</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="outline" class="w-full">
                            <Link :href="route('admin.users.index')">View All Users</Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Recent Newsletter Subscriptions and Contact Submissions -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Newsletter Subscriptions -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Recent Newsletter Subscriptions
                        </CardTitle>
                        <CardDescription>Latest newsletter sign-ups</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <div v-for="subscription in recentData.newsletter" :key="subscription.id" class="flex items-center justify-between p-3 rounded-lg border">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ subscription.name || 'Anonymous' }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ subscription.email }}</p>
                                    <Badge :class="getStatusColor(subscription.status)" class="text-xs mt-1">{{ subscription.status }}</Badge>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">{{ subscription.created_at_human }}</p>
                                </div>
                            </div>
                            <div v-if="recentData.newsletter.length === 0" class="text-center py-8 text-muted-foreground">
                                <Mail class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>No recent subscriptions</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="outline" class="w-full">
                            <Link :href="route('admin.newsletter-subscriptions.index')">View All Subscriptions</Link>
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Recent Contact Submissions -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MessageSquare class="h-5 w-5" />
                            Recent Contact Submissions
                        </CardTitle>
                        <CardDescription>Latest contact form submissions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <div v-for="contact in recentData.contacts" :key="contact.id" class="flex items-center justify-between p-3 rounded-lg border">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ contact.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ contact.subject }}</p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <Badge :class="getStatusColor(contact.status)" class="text-xs">{{ contact.status }}</Badge>
                                        <Badge :class="getPriorityColor(contact.priority)" class="text-xs">{{ contact.priority }}</Badge>
                                    </div>
                                    <p v-if="contact.assigned_user" class="text-xs text-muted-foreground mt-1">
                                        Assigned to {{ contact.assigned_user.name }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">{{ contact.created_at_human }}</p>
                                    <Button as-child variant="ghost" size="sm">
                                        <Link :href="route('admin.contact-submissions.index')">
                                            <Eye class="h-3 w-3" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                            <div v-if="recentData.contacts.length === 0" class="text-center py-8 text-muted-foreground">
                                <MessageSquare class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>No recent contacts</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button as-child variant="outline" class="w-full">
                            <Link :href="route('admin.contact-submissions.index')">View All Contacts</Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription>Frequently used admin functions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button as-child variant="outline" class="h-20 flex-col">
                            <Link :href="route('admin.users.create')">
                                <Users class="h-6 w-6 mb-2" />
                                Create User
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-20 flex-col">
                            <Link :href="route('admin.roles-permissions')">
                                <Shield class="h-6 w-6 mb-2" />
                                Manage Roles
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-20 flex-col">
                            <Link :href="route('admin.stripe.dashboard')">
                                <CreditCard class="h-6 w-6 mb-2" />
                                Stripe Dashboard
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="h-20 flex-col">
                            <Link :href="route('admin.notifications.index')">
                                <AlertTriangle class="h-6 w-6 mb-2" />
                                Notifications
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>