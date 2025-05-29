<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Icon name="calendar" class="h-5 w-5" />
          Schedule a Demo
        </DialogTitle>
        <DialogDescription>
          Book a personalized demo of RillTech's AI agent platform. We'll show you how to build and deploy AI assistants in minutes.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Personal Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Contact Information</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="name">Full Name *</Label>
              <Input
                id="name"
                v-model="form.name"
                placeholder="John Doe"
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <div class="space-y-2">
              <Label for="email">Email Address *</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="john@company.com"
                :class="{ 'border-red-500': form.errors.email }"
              />
              <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="company">Company</Label>
              <Input
                id="company"
                v-model="form.company"
                placeholder="Your Company"
              />
            </div>

            <div class="space-y-2">
              <Label for="phone">Phone Number</Label>
              <Input
                id="phone"
                v-model="form.phone"
                placeholder="+1 (555) 123-4567"
              />
            </div>
          </div>
        </div>

        <!-- Demo Type -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Demo Type</h3>

          <div class="space-y-2">
            <Label for="demo_type">What type of demo are you interested in? *</Label>
            <Select v-model="form.demo_type">
              <SelectTrigger :class="{ 'border-red-500': form.errors.demo_type }">
                <SelectValue placeholder="Select demo type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="general">General Platform Demo</SelectItem>
                <SelectItem value="enterprise">Enterprise Features Demo</SelectItem>
                <SelectItem value="specific-feature">Specific Feature Demo</SelectItem>
                <SelectItem value="custom">Custom Demo</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.demo_type" class="text-sm text-red-500">{{ form.errors.demo_type }}</p>
          </div>
        </div>

        <!-- Scheduling -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Preferred Date & Time</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="date">Preferred Date *</Label>
              <Input
                id="date"
                v-model="form.date"
                type="date"
                :min="minDate"
                :class="{ 'border-red-500': form.errors.preferred_datetime }"
                @change="onDateChange"
              />
            </div>

            <div class="space-y-2">
              <Label for="time">Preferred Time *</Label>
              <Select v-model="form.time" :disabled="!form.date || loadingSlots">
                <SelectTrigger :class="{ 'border-red-500': form.errors.preferred_datetime }">
                  <SelectValue placeholder="Select time" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="slot in availableSlots"
                    :key="slot.time"
                    :value="slot.time"
                    :disabled="!slot.available"
                  >
                    {{ slot.formatted }}
                    <span v-if="!slot.available" class="text-muted-foreground ml-2">(Unavailable)</span>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.preferred_datetime" class="text-sm text-red-500">{{ form.errors.preferred_datetime }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="timezone">Timezone *</Label>
            <Select v-model="form.timezone">
              <SelectTrigger :class="{ 'border-red-500': form.errors.timezone }">
                <SelectValue placeholder="Select timezone" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="America/New_York">Eastern Time (ET)</SelectItem>
                <SelectItem value="America/Chicago">Central Time (CT)</SelectItem>
                <SelectItem value="America/Denver">Mountain Time (MT)</SelectItem>
                <SelectItem value="America/Los_Angeles">Pacific Time (PT)</SelectItem>
                <SelectItem value="UTC">UTC</SelectItem>
                <SelectItem value="Europe/London">London (GMT)</SelectItem>
                <SelectItem value="Europe/Paris">Paris (CET)</SelectItem>
                <SelectItem value="Asia/Tokyo">Tokyo (JST)</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.timezone" class="text-sm text-red-500">{{ form.errors.timezone }}</p>
          </div>
        </div>

        <!-- Additional Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold">Additional Information</h3>

          <div class="space-y-2">
            <Label for="message">Message (Optional)</Label>
            <Textarea
              id="message"
              v-model="form.message"
              placeholder="Tell us about your specific needs or questions..."
              rows="3"
            />
            <p class="text-sm text-muted-foreground">
              Let us know what you'd like to focus on during the demo.
            </p>
          </div>
        </div>

        <!-- Suggested Times (if original time unavailable) -->
        <div v-if="suggestedTimes.length > 0" class="space-y-4">
          <h3 class="text-lg font-semibold text-orange-600">Alternative Times Available</h3>
          <p class="text-sm text-muted-foreground">
            Your preferred time is not available. Here are some alternative options:
          </p>
          <div class="grid gap-2">
            <button
              v-for="(suggestion, index) in suggestedTimes"
              :key="index"
              type="button"
              @click="selectSuggestedTime(suggestion)"
              class="p-3 text-left border rounded-lg hover:bg-muted transition-colors"
            >
              <div class="font-medium">{{ suggestion.formatted }}</div>
              <div class="text-sm text-muted-foreground">{{ suggestion.timezone }}</div>
            </button>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal" :disabled="form.processing">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing || !isFormValid">
            <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
            <Icon v-else name="calendar-check" class="mr-2 h-4 w-4" />
            {{ form.processing ? 'Scheduling...' : 'Schedule Demo' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import axios from 'axios'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import Icon from '@/Components/Icon.vue'

interface Props {
  open?: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'success', data: any): void
}

const props = withDefaults(defineProps<Props>(), {
  open: false
})

const emit = defineEmits<Emits>()

// Reactive state
const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

// Inertia form
const form = useForm({
  name: '',
  email: '',
  company: '',
  phone: '',
  demo_type: '',
  date: '',
  time: '',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  message: ''
})

const loadingSlots = ref(false)
const availableSlots = ref<Array<{time: string, formatted: string, available: boolean}>>([])
const suggestedTimes = ref<Array<{datetime: string, formatted: string, timezone: string}>>([])

// Computed properties
const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const isFormValid = computed(() => {
  return form.name &&
         form.email &&
         form.demo_type &&
         form.date &&
         form.time &&
         form.timezone
})

// Methods
const closeModal = () => {
  isOpen.value = false
  resetForm()
}

const resetForm = () => {
  form.reset()
  form.clearErrors()
  suggestedTimes.value = []
  availableSlots.value = []
}

const onDateChange = async () => {
  if (form.date && form.timezone) {
    await fetchAvailableSlots()
  }
}

const fetchAvailableSlots = async () => {
  if (!form.date || !form.timezone) return

  loadingSlots.value = true
  try {
    const response = await axios.get('/api/demo-requests/available-slots', {
      params: {
        date: form.date,
        timezone: form.timezone
      }
    })

    availableSlots.value = response.data.slots || []
  } catch (error) {
    console.error('Error fetching available slots:', error)
    toast.error('Failed to load available time slots')
  } finally {
    loadingSlots.value = false
  }
}

const selectSuggestedTime = (suggestion: any) => {
  const datetime = new Date(suggestion.datetime)
  form.date = datetime.toISOString().split('T')[0]
  form.time = datetime.toTimeString().slice(0, 5)
  form.timezone = suggestion.timezone
  suggestedTimes.value = []

  nextTick(() => {
    fetchAvailableSlots()
  })
}

const submitForm = async () => {
  if (!isFormValid.value) return

  // Combine date and time
  const datetime = new Date(`${form.date}T${form.time}:00`)

  const payload = {
    name: form.name,
    email: form.email,
    company: form.company || null,
    phone: form.phone || null,
    demo_type: form.demo_type,
    preferred_datetime: datetime.toISOString(),
    timezone: form.timezone,
    message: form.message || null
  }

  try {
    const response = await axios.post('/api/demo-requests', payload)

    if (response.data.success) {
      toast.success('Demo scheduled successfully! We will contact you soon to confirm the details.')
      emit('success', response.data.demo_request)
      closeModal()
    } else {
      throw new Error(response.data.message || 'Failed to schedule demo')
    }

  } catch (error: any) {
    if (error.response?.status === 409) {
      // Time slot not available - show suggested times
      const data = error.response.data
      suggestedTimes.value = data.suggested_times || []
      toast.error(data.message || 'The requested time slot is not available')
    } else if (error.response?.status === 422) {
      // Validation errors - Inertia will handle these automatically
      toast.error('Please check the form for errors')
    } else {
      toast.error(error.response?.data?.message || 'Failed to schedule demo. Please try again.')
    }
  }
}

// Watch for timezone changes to refetch slots
watch(() => form.timezone, () => {
  if (form.date) {
    fetchAvailableSlots()
  }
})
</script>
