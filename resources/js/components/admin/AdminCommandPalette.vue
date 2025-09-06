<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Command,
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
  CommandShortcut,
} from '@/components/ui/command'
import { VisuallyHidden } from '@/components/ui/visually-hidden'
import {
  Users,
  Mail,
  MessageSquare,
  Shield,
  CreditCard,
  UserPlus,
  FileText,
  Download,
  Home,
  Bell,
  LogOut,
  User,
  Key,
  Activity,
  Calendar
} from 'lucide-vue-next'

interface CommandItem {
  id: string
  title: string
  description?: string
  icon: any
  action: () => void
  keywords: string[]
  group: string
  shortcut?: string
}

const open = ref(false)

// Define all available commands
const commands = computed<CommandItem[]>(() => [
  // Navigation Commands
  {
    id: 'nav-dashboard',
    title: 'Dashboard',
    description: 'Go to admin dashboard',
    icon: Home,
    action: () => router.visit('/admin/dashboard'),
    keywords: ['dashboard', 'home', 'overview'],
    group: 'Navigation',
    shortcut: '⌘D'
  },
  {
    id: 'nav-users',
    title: 'Users',
    description: 'Manage users',
    icon: Users,
    action: () => router.visit('/admin/users'),
    keywords: ['users', 'accounts', 'members'],
    group: 'Navigation',
    shortcut: '⌘U'
  },
  {
    id: 'nav-newsletter',
    title: 'Newsletter Subscriptions',
    description: 'Manage newsletter subscriptions',
    icon: Mail,
    action: () => router.visit('/admin/newsletter-subscriptions'),
    keywords: ['newsletter', 'subscriptions', 'email', 'subscribers'],
    group: 'Navigation',
    shortcut: '⌘N'
  },
  {
    id: 'nav-contacts',
    title: 'Contact Submissions',
    description: 'Manage contact form submissions',
    icon: MessageSquare,
    action: () => router.visit('/admin/contact-submissions'),
    keywords: ['contacts', 'submissions', 'messages', 'support'],
    group: 'Navigation',
    shortcut: '⌘C'
  },
  {
    id: 'nav-roles',
    title: 'Roles & Permissions',
    description: 'Manage roles and permissions',
    icon: Shield,
    action: () => router.visit('/admin/roles-permissions'),
    keywords: ['roles', 'permissions', 'access', 'security'],
    group: 'Navigation',
    shortcut: '⌘R'
  },
  {
    id: 'nav-stripe',
    title: 'Stripe Dashboard',
    description: 'Manage Stripe payments',
    icon: CreditCard,
    action: () => router.visit('/admin/stripe/dashboard'),
    keywords: ['stripe', 'payments', 'billing', 'subscriptions'],
    group: 'Navigation',
    shortcut: '⌘S'
  },
  {
    id: 'nav-demo-requests',
    title: 'Demo Requests',
    description: 'Manage demo requests',
    icon: Calendar,
    action: () => router.visit('/admin/demo-requests'),
    keywords: ['demo', 'requests', 'scheduling', 'appointments'],
    group: 'Navigation',
    shortcut: '⌘M'
  },

  // Quick Actions
  {
    id: 'action-create-user',
    title: 'Create New User',
    description: 'Add a new user to the system',
    icon: UserPlus,
    action: () => router.visit('/admin/users/create'),
    keywords: ['create', 'add', 'new', 'user', 'account'],
    group: 'Quick Actions'
  },
  {
    id: 'action-export-users',
    title: 'Export Users',
    description: 'Export users to Excel',
    icon: Download,
    action: () => window.open('/admin/users/export-excel', '_blank'),
    keywords: ['export', 'download', 'users', 'excel'],
    group: 'Quick Actions'
  },
  {
    id: 'action-export-newsletter',
    title: 'Export Newsletter Subscriptions',
    description: 'Export newsletter subscriptions to Excel',
    icon: Download,
    action: () => window.open('/admin/newsletter-subscriptions/export-excel', '_blank'),
    keywords: ['export', 'download', 'newsletter', 'subscriptions', 'excel'],
    group: 'Quick Actions'
  },
  {
    id: 'action-export-contacts',
    title: 'Export Contact Submissions',
    description: 'Export contact submissions to Excel',
    icon: Download,
    action: () => window.open('/admin/contact-submissions/export-excel', '_blank'),
    keywords: ['export', 'download', 'contacts', 'submissions', 'excel'],
    group: 'Quick Actions'
  },

  // Settings & Profile
  {
    id: 'settings-profile',
    title: 'Profile Settings',
    description: 'Update your profile information',
    icon: User,
    action: () => router.visit('/admin/settings/profile'),
    keywords: ['profile', 'settings', 'account', 'personal'],
    group: 'Settings'
  },
  {
    id: 'settings-password',
    title: 'Change Password',
    description: 'Update your password',
    icon: Key,
    action: () => router.visit('/admin/settings/password'),
    keywords: ['password', 'security', 'change', 'update'],
    group: 'Settings'
  },
  {
    id: 'settings-sessions',
    title: 'Active Sessions',
    description: 'Manage your active sessions',
    icon: Activity,
    action: () => router.visit('/admin/settings/sessions'),
    keywords: ['sessions', 'security', 'devices', 'logout'],
    group: 'Settings'
  },

  // System Actions
  {
    id: 'system-activity-logs',
    title: 'View Activity Logs',
    description: 'View system activity logs',
    icon: FileText,
    action: () => {
      // Since we don't have a dedicated activity logs page, scroll to activity section on dashboard
      router.visit('/admin/dashboard')
      setTimeout(() => {
        const activitySection = document.querySelector('[data-activity-logs]')
        if (activitySection) {
          activitySection.scrollIntoView({ behavior: 'smooth' })
        }
      }, 500)
    },
    keywords: ['activity', 'logs', 'audit', 'history'],
    group: 'System'
  },
  {
    id: 'system-notifications',
    title: 'View Notifications',
    description: 'Check system notifications',
    icon: Bell,
    action: () => {
      // Trigger notification dropdown
      const notificationButton = document.querySelector('[data-notification-trigger]')
      if (notificationButton) {
        (notificationButton as HTMLElement).click()
      }
    },
    keywords: ['notifications', 'alerts', 'messages'],
    group: 'System'
  },

  // Logout
  {
    id: 'auth-logout',
    title: 'Logout',
    description: 'Sign out of your account',
    icon: LogOut,
    action: () => router.post('/logout'),
    keywords: ['logout', 'signout', 'exit', 'leave'],
    group: 'Account'
  }
])

// Keyboard shortcuts
const handleKeyDown = (event: KeyboardEvent) => {
  // Open command palette with Cmd+K or Ctrl+K
  if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
    event.preventDefault()
    open.value = !open.value
    return
  }

  // Handle specific shortcuts when palette is closed
  if (!open.value && (event.metaKey || event.ctrlKey)) {
    const shortcutCommands = commands.value.filter(cmd => cmd.shortcut)
    const shortcutKey = event.key.toLowerCase()

    const command = shortcutCommands.find(cmd => {
      const shortcut = cmd.shortcut?.toLowerCase()
      return shortcut?.includes(shortcutKey)
    })

    if (command) {
      event.preventDefault()
      command.action()
    }
  }
}

// Execute command and close palette
const executeCommand = (command: CommandItem) => {
  command.action()
  open.value = false
}

// Group commands by category
const groupedCommands = computed(() => {
  const groups: Record<string, CommandItem[]> = {}
  commands.value.forEach(command => {
    if (!groups[command.group]) {
      groups[command.group] = []
    }
    groups[command.group].push(command)
  })
  return groups
})

// Handle custom event to open command palette
const handleOpenCommandPalette = () => {
  open.value = true
}

onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
  window.addEventListener('open-command-palette', handleOpenCommandPalette)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('open-command-palette', handleOpenCommandPalette)
})


</script>

<template>
  <CommandDialog v-model:open="open">
    <VisuallyHidden>
      <h2>Command Palette</h2>
      <p>Search and navigate through admin functions quickly</p>
    </VisuallyHidden>
    <Command class="rounded-lg border shadow-md">
      <CommandInput placeholder="Type a command or search..." />
      <CommandList>
        <CommandEmpty>No results found.</CommandEmpty>

        <template v-for="(groupCommands, groupName) in groupedCommands" :key="groupName">
          <CommandGroup :heading="groupName">
            <CommandItem
              v-for="command in groupCommands"
              :key="command.id"
              :value="command.keywords.join(' ')"
              @select="executeCommand(command)"
              class="flex items-center gap-3 px-3 py-2 cursor-pointer"
            >
              <component :is="command.icon" class="h-4 w-4 text-muted-foreground" />
              <div class="flex-1 min-w-0">
                <div class="font-medium">{{ command.title }}</div>
                <div v-if="command.description" class="text-sm text-muted-foreground truncate">
                  {{ command.description }}
                </div>
              </div>
              <CommandShortcut v-if="command.shortcut">
                {{ command.shortcut }}
              </CommandShortcut>
            </CommandItem>
          </CommandGroup>
          <CommandSeparator v-if="Object.keys(groupedCommands).indexOf(groupName) < Object.keys(groupedCommands).length - 1" />
        </template>
      </CommandList>
    </Command>
  </CommandDialog>
</template>
