<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { CheckCircle, Sparkles, ArrowRight } from 'lucide-vue-next';

interface Props {
  open: boolean;
  sessionId?: string;
  planName?: string;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  sessionId: '',
  planName: 'Premium Plan'
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  close: [];
}>();

const isOpen = computed({
  get: () => props.open,
  set: (value: boolean) => {
    emit('update:open', value);
    if (!value) {
      emit('close');
    }
  }
});

const handleClose = () => {
  isOpen.value = false;
};

const handleGetStarted = () => {
  handleClose();
  // Could scroll to a specific section or trigger a tour
};


</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader class="text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
          <CheckCircle class="h-8 w-8 text-green-600 dark:text-green-400" />
        </div>
        <DialogTitle class="text-2xl font-bold">
          Welcome to {{ planName }}! 🎉
        </DialogTitle>
        <DialogDescription class="text-base">
          Your subscription is now active and you have full access to all premium features.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/10">
          <div class="flex items-start space-x-3">
            <Sparkles class="mt-0.5 h-5 w-5 text-green-600 dark:text-green-400" />
            <div>
              <h4 class="font-medium text-green-900 dark:text-green-100">
                What's included in your plan:
              </h4>
              <ul class="mt-2 space-y-1 text-sm text-green-800 dark:text-green-200">
                <li>• Unlimited AI agent creation</li>
                <li>• Advanced customization options</li>
                <li>• Priority customer support</li>
                <li>• Analytics and insights</li>
                <li>• API access</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/10">
          <div class="flex items-start space-x-3">
            <ArrowRight class="mt-0.5 h-5 w-5 text-blue-600 dark:text-blue-400" />
            <div>
              <h4 class="font-medium text-blue-900 dark:text-blue-100">
                Next steps:
              </h4>
              <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                Explore your dashboard to start building your first AI agent. Check out our getting started guide for tips and best practices.
              </p>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="flex-col space-y-2 sm:flex-row sm:space-x-2 sm:space-y-0">
        <Button variant="outline" @click="handleClose" class="w-full sm:w-auto">
          Explore Later
        </Button>
        <Button @click="handleGetStarted" class="w-full sm:w-auto">
          Get Started Now
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
