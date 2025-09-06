<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { preloader } from '@/composables/usePreloader';

interface Props {
  show?: boolean;
  duration?: number;
  logoText?: string;
  loadingText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  show: true,
  duration: 2000,
  logoText: 'RillTech',
  loadingText: 'Loading...'
});

const emit = defineEmits<{
  (e: 'loaded'): void;
}>();

const isVisible = ref(props.show);
const progress = ref(0);
const loadingStage = ref(0); // 0: loading, 1: complete, 2: fade out

// Watch for external progress updates
watch(() => preloader.loadingProgress.value, (newProgress) => {
  progress.value = newProgress;
  if (newProgress >= 100) {
    loadingStage.value = 1;
  }
}, { immediate: true });

let progressInterval: number;
let hideTimeout: number;

const startLoading = () => {
  // Only simulate loading progress if duration is set (internal mode)
  if (props.duration > 0) {
    progressInterval = setInterval(() => {
      if (progress.value < 100) {
        // Simulate realistic loading with varying speeds
        const increment = Math.random() * 15 + 5;
        progress.value = Math.min(100, progress.value + increment);
      } else {
        clearInterval(progressInterval);
        loadingStage.value = 1;

        // Wait a bit then start fade out
        hideTimeout = setTimeout(() => {
          loadingStage.value = 2;

          // Hide completely after fade animation
          setTimeout(() => {
            isVisible.value = false;
            emit('loaded');
          }, 500);
        }, 300);
      }
    }, 100);
  }
};

// Watch for show prop changes
watch(() => props.show, (newShow) => {
  isVisible.value = newShow;
  if (newShow && props.duration > 0) {
    startLoading();
  }
}, { immediate: true });

// Watch for loading stage changes to trigger fade out
watch(() => loadingStage.value, (newStage) => {
  if (newStage === 1 && props.duration === 0) {
    // External control mode - wait a bit then fade out
    hideTimeout = setTimeout(() => {
      loadingStage.value = 2;

      // Hide completely after fade animation
      setTimeout(() => {
        isVisible.value = false;
        emit('loaded');
      }, 500);
    }, 300);
  }
});

onMounted(() => {
  // Component is now controlled by watchers
});

onUnmounted(() => {
  if (progressInterval) clearInterval(progressInterval);
  if (hideTimeout) clearTimeout(hideTimeout);
});

// Manual trigger for external control
const hide = () => {
  loadingStage.value = 2;
  setTimeout(() => {
    isVisible.value = false;
    emit('loaded');
  }, 500);
};

defineExpose({ hide });
</script>

<template>
  <Transition
    enter-active-class="transition-opacity duration-300"
    leave-active-class="transition-opacity duration-500"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isVisible"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-background"
      :class="{
        'animate-fade-out': loadingStage === 2
      }"
    >
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-primary/5"></div>
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, hsl(var(--primary)) 0%, transparent 50%), radial-gradient(circle at 75% 75%, hsl(var(--primary)) 0%, transparent 50%); opacity: 0.1;"></div>
      </div>

      <!-- Main Content -->
      <div class="relative flex flex-col items-center space-y-8">
        <!-- Logo/Brand -->
        <div class="text-center">
          <div class="relative">
            <!-- Animated Logo -->
            <div class="flex items-center justify-center">
              <div class="relative">
                <!-- Logo Icon -->
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 backdrop-blur-sm">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="32"
                    height="32"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="text-primary animate-pulse"
                  >
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                  </svg>
                </div>

                <!-- Rotating Ring -->
                <div class="absolute inset-0 animate-spin-slow">
                  <div class="h-16 w-16 rounded-2xl border-2 border-transparent border-t-primary/30"></div>
                </div>
              </div>
            </div>

            <!-- Brand Text -->
            <h1 class="mt-4 text-2xl font-bold text-foreground">
              {{ logoText }}
            </h1>
          </div>
        </div>

        <!-- Loading Progress -->
        <div class="w-64 space-y-4">
          <!-- Progress Bar -->
          <div class="relative">
            <div class="h-1 w-full overflow-hidden rounded-full bg-muted">
              <div
                class="h-full bg-gradient-to-r from-primary to-primary/80 transition-all duration-300 ease-out"
                :style="{ width: `${progress}%` }"
              ></div>
            </div>
            <!-- Progress Glow -->
            <div
              class="absolute top-0 h-1 bg-primary/50 blur-sm transition-all duration-300 ease-out"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>

          <!-- Loading Text and Percentage -->
          <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">{{ loadingText }}</span>
            <span class="font-mono text-primary">{{ Math.round(progress) }}%</span>
          </div>
        </div>

        <!-- Loading Dots Animation -->
        <div class="flex space-x-1">
          <div
            v-for="i in 3"
            :key="i"
            class="h-2 w-2 rounded-full bg-primary/60 animate-bounce"
            :style="{ animationDelay: `${(i - 1) * 0.2}s` }"
          ></div>
        </div>

        <!-- Success State -->
        <Transition
          enter-active-class="transition-all duration-500"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
        >
          <div v-if="loadingStage >= 1" class="flex items-center space-x-2 text-green-600 dark:text-green-400">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="animate-scale-in"
            >
              <path d="M20 6L9 17l-5-5"/>
            </svg>
            <span class="text-sm font-medium">Ready!</span>
          </div>
        </Transition>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes scale-in {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

@keyframes fade-out {
  to {
    opacity: 0;
    transform: scale(0.95);
  }
}

.animate-spin-slow {
  animation: spin-slow 3s linear infinite;
}

.animate-scale-in {
  animation: scale-in 0.5s ease-out;
}

.animate-fade-out {
  animation: fade-out 0.5s ease-out forwards;
}
</style>
