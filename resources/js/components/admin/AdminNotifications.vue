<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Bell, Trash2, UserPlus, AlertCircle, CheckCircle, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuItem
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { toast } from 'vue-sonner';
import { formatDistanceToNow } from 'date-fns';
import axios from 'axios';
import { useEcho } from "@laravel/echo-vue";


interface Notification {
    id: string;
    type: string;
    data: {
            id: number;
            name: string;
            email: string;
            created_at: string;
    };
    read_at: string | null;
    created_at: string;
}

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(true);
const page = usePage();
const user = computed(() => page.props.auth.user);

// Load notifications
const loadNotifications = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/admin/notifications');
        notifications.value = response.data.notifications;
        unreadCount.value = notifications.value.filter(n => !n.read_at).length;
    } catch (error) {
        console.error('Failed to load notifications', error);
    } finally {
        loading.value = false;
    }
};

// Mark notification as read
const markAsRead = async (notification: Notification) => {
    if (notification.read_at) return;

    try {
        await axios.post(`/admin/notifications/${notification.id}/read`);

        const index = notifications.value.findIndex(n => n.id === notification.id);
        if (index !== -1) {
            notifications.value[index].read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark notification as read', error);
    }
};

// Mark all notifications as read
const markAllAsRead = async () => {
    try {
        await axios.post('/admin/notifications/mark-all-read');

        notifications.value = notifications.value.map(notification => ({
            ...notification,
            read_at: notification.read_at || new Date().toISOString()
        }));
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all notifications as read', error);
    }
};

// Delete a notification
const deleteNotification = async (notification: Notification, event: Event) => {
    // Stop event propagation to prevent markAsRead from being called
    event.stopPropagation();

    try {
        await axios.delete(`/admin/notifications/${notification.id}`);

        // Remove the notification from the list
        notifications.value = notifications.value.filter(n => n.id !== notification.id);

        // Update unread count if the notification was unread
        if (!notification.read_at) {
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }

        toast('Notification deleted', {
            description: 'The notification has been deleted successfully'
        });
    } catch (error) {
        console.error('Failed to delete notification', error);
        toast('Error', {
            description: 'Failed to delete notification',
            variant: 'destructive'
        });
    }
};

// Delete all notifications
const deleteAllNotifications = async () => {
    try {
        await axios.delete('/admin/notifications');

        // Clear the notifications list
        notifications.value = [];
        unreadCount.value = 0;

        toast.success('All notifications deleted', {
            description: 'All notifications have been deleted successfully'
        });
    } catch (error) {
        console.error('Failed to delete all notifications', error);
        toast.error('Error', {
            description: 'Failed to delete all notifications',

        });
    }
};

// Format date
const formatDate = (date: string) => {
    return formatDistanceToNow(new Date(date), { addSuffix: true });
};

// Get notification icon based on type
const getNotificationIcon = (type: string) => {
    switch (type) {
        case 'user_registered':
            return UserPlus;
        case 'error':
            return AlertCircle;
        case 'success':
            return CheckCircle;
        case 'info':
            return FileText;
        default:
            return Bell;
    }
};

// Get notification color based on type
const getNotificationColor = (type: string) => {
    switch (type) {
        case 'user_registered':
            return 'text-blue-500';
        case 'error':
            return 'text-red-500';
        case 'success':
            return 'text-green-500';
        case 'info':
            return 'text-yellow-500';
        default:
            return 'text-gray-500';
    }
};

// Listen for new notifications
useEcho(
    `App.Models.User.${user.value.id}`,
    '.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
    (e: any) => {
        // console.log(e);

        // Add the new notification to the list
        const newNotification = {
            id: e.id,
            type: e.type,
            data: e.user,
            read_at: null,
            created_at: e.created_at || new Date().toISOString()
        };
        // console.log(newNotification);
        notifications.value = [newNotification, ...notifications.value];
        unreadCount.value++;

        // Show toast notification
        if (e.user && typeof e.user === 'object') {
            const message = e.message || 'New notification received';
            let description = '';
            const notificationType = e.type  || 'info';

            if (e.user && typeof e.user === 'object') {
                const userName = e.user.name || 'Unknown';
                const userEmail = e.user.email || '';
                description = userEmail ? `${userName} (${userEmail})` : userName;
            }

            // Use different toast variants based on notification type
            switch (notificationType) {
                case 'App\\Notifications\\NewUserRegistered':
                    toast.info(message, {
                        description: description
                     });
                    break;
                case 'error':
                    toast.error(message, {
                        description : description});
                    break;
                case 'success':
                    toast.success(message, {
                        description: description });
                    break;
                case 'info':
                    toast.info(message, {
                        description: description});
                    break;
                default:
                    toast(message, {
                        description: description });
            }
        } else {
            // Fallback if data is not in expected format
            toast.info('New notification received', {
                description: 'Unknown content'
            });
        }
    }
);

onMounted(() => {
    loadNotifications();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <Badge v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex justify-between items-center">
                <span>Notifications</span>
                <div class="flex gap-2">
                    <Button v-if="unreadCount > 0" variant="ghost" size="sm" @click="markAllAsRead" class="text-xs h-7">
                        Mark all as read
                    </Button>
                    <Button v-if="notifications.length > 0" variant="ghost" size="sm" @click="deleteAllNotifications"
                        class="text-xs h-7 text-destructive hover:text-destructive">
                        Delete all
                    </Button>
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="loading" class="py-6 text-center text-sm text-muted-foreground">
                Loading notifications...
            </div>

            <div v-else-if="notifications.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                No notifications yet
            </div>

            <div v-else class="max-h-[300px] overflow-y-auto">
                <DropdownMenuItem v-for="notification in notifications" :key="notification.id"
                    @click="markAsRead(notification)" :class="[
                        'flex flex-col items-start py-2 px-4 cursor-pointer',
                        { 'bg-muted/50': !notification.read_at }
                    ]">
                    <div class="flex justify-between w-full">
                        <div class="flex items-center gap-2 ">
                            <component :is="getNotificationIcon(notification.type || '')" class="h-4 w-4"
                                :class="getNotificationColor(notification.type || '')" />
                            <div class="flex flex-col">
                                <span v-if="notification.data?.email" class="text-xs text-muted-foreground">
                                    {{ notification.data.email }}
                                </span>
                                <span v-if="notification.data?.name" class="text-xs text-muted-foreground">
                                    {{ notification.data.name }}
                                </span>
                                <span v-if="notification.data?.created_at" class="text-xs text-muted-foreground">
                                    Joined {{ formatDate(notification.data.created_at) }}
                                </span>
                            </div>
                        </div>
                        <div class="right-0">
                            <Button variant="ghost" size="icon" class="h-5 w-5 text-destructive hover:text-destructive"
                                @click="(e) => deleteNotification(notification, e)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="flex flex-col mt-1 mx-6">
                        <small class="text-xs text-muted-foreground">{{ formatDate(notification.created_at)}}</small>
                    </div>

                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
