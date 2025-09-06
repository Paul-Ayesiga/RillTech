<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle, Trash2, X } from 'lucide-vue-next';

interface Props {
  open: boolean;
  title?: string;
  description?: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'destructive' | 'default';
  icon?: 'warning' | 'delete' | 'none';
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Confirm Action',
  description: 'Are you sure you want to proceed? This action cannot be undone.',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  variant: 'destructive',
  icon: 'warning',
  loading: false,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  confirm: [];
  cancel: [];
}>();

const isOpen = ref(props.open);

watch(() => props.open, (newValue) => {
  isOpen.value = newValue;
});

watch(isOpen, (newValue) => {
  emit('update:open', newValue);
});

const handleConfirm = () => {
  if (!props.loading) {
    emit('confirm');
  }
};

const handleCancel = () => {
  if (!props.loading) {
    isOpen.value = false;
    emit('cancel');
  }
};

const getIcon = () => {
  switch (props.icon) {
    case 'warning':
      return AlertTriangle;
    case 'delete':
      return Trash2;
    default:
      return null;
  }
};

const getIconColor = () => {
  switch (props.variant) {
    case 'destructive':
      return 'text-red-600';
    default:
      return 'text-yellow-600';
  }
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div 
            v-if="icon !== 'none'" 
            class="flex h-10 w-10 items-center justify-center rounded-full"
            :class="variant === 'destructive' ? 'bg-red-100' : 'bg-yellow-100'"
          >
            <component 
              :is="getIcon()" 
              class="h-5 w-5"
              :class="getIconColor()"
            />
          </div>
          <div class="flex-1">
            <DialogTitle class="text-left">{{ title }}</DialogTitle>
            <DialogDescription class="text-left mt-1">
              {{ description }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>
      
      <DialogFooter class="flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
        <Button 
          variant="outline" 
          @click="handleCancel"
          :disabled="loading"
          class="mt-3 sm:mt-0"
        >
          <X class="mr-2 h-4 w-4" />
          {{ cancelText }}
        </Button>
        <Button 
          :variant="variant"
          @click="handleConfirm"
          :disabled="loading"
          class="w-full sm:w-auto"
        >
          <component 
            :is="loading ? 'div' : getIcon()" 
            class="mr-2 h-4 w-4"
            :class="loading ? 'animate-spin rounded-full border-2 border-current border-t-transparent' : ''"
          />
          {{ loading ? 'Processing...' : confirmText }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
