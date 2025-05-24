<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { gsap } from 'gsap';

interface Props {
  showAfter?: number;
  position?: 'bottom-right' | 'bottom-left' | 'bottom-center';
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'outline' | 'ghost';
  smooth?: boolean;
  duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
  showAfter: 300,
  position: 'bottom-right',
  size: 'md',
  variant: 'default',
  smooth: true,
  duration: 1000
});

const emit = defineEmits<{
  (e: 'scroll-start'): void;
  (e: 'scroll-complete'): void;
}>();

const isVisible = ref(false);
const isScrolling = ref(false);
const scrollProgress = ref(0);
const buttonRef = ref<HTMLElement>();

let ticking = false;

// Calculate scroll progress
const updateScrollProgress = () => {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
  const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
  
  scrollProgress.value = Math.min(100, Math.max(0, progress));
  isVisible.value = scrollTop > props.showAfter;
};

// Throttled scroll handler
const handleScroll = () => {
  if (!ticking) {
    requestAnimationFrame(() => {
      updateScrollProgress();
      ticking = false;
    });
    ticking = true;
  }
};

// Smooth scroll to top
const scrollToTop = () => {
  if (isScrolling.value) return;
  
  isScrolling.value = true;
  emit('scroll-start');

  if (props.smooth && typeof gsap !== 'undefined') {
    // Use GSAP for smooth scrolling
    gsap.to(window, {
      duration: props.duration / 1000,
      scrollTo: { y: 0, autoKill: false },
      ease: 'power2.out',
      onComplete: () => {
        isScrolling.value = false;
        emit('scroll-complete');
      }
    });
  } else {
    // Fallback to native smooth scrolling
    window.scrollTo({
      top: 0,
      behavior: props.smooth ? 'smooth' : 'auto'
    });
    
    // Estimate completion time for native smooth scroll
    setTimeout(() => {
      isScrolling.value = false;
      emit('scroll-complete');
    }, props.duration);
  }

  // Add button animation feedback
  if (buttonRef.value) {
    gsap.to(buttonRef.value, {
      scale: 0.95,
      duration: 0.1,
      yoyo: true,
      repeat: 1,
      ease: 'power2.out'
    });
  }
};

// Position classes
const positionClasses = {
  'bottom-right': 'bottom-6 right-6',
  'bottom-left': 'bottom-6 left-6',
  'bottom-center': 'bottom-6 left-1/2 -translate-x-1/2'
};

// Size classes
const sizeClasses = {
  sm: 'h-10 w-10',
  md: 'h-12 w-12',
  lg: 'h-14 w-14'
};

// Variant classes
const variantClasses = {
  default: 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-lg',
  outline: 'border border-border bg-background/80 text-foreground hover:bg-accent hover:text-accent-foreground',
  ghost: 'bg-background/60 text-foreground hover:bg-accent hover:text-accent-foreground'
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  updateScrollProgress(); // Initial check
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    leave-active-class="transition-all duration-200 ease-in"
    enter-from-class="opacity-0 scale-75 translate-y-4"
    enter-to-class="opacity-100 scale-100 translate-y-0"
    leave-from-class="opacity-100 scale-100 translate-y-0"
    leave-to-class="opacity-0 scale-75 translate-y-4"
  >
    <button
      v-if="isVisible"
      ref="buttonRef"
      @click="scrollToTop"
      :disabled="isScrolling"
      :class="[
        'fixed z-50 flex items-center justify-center rounded-full backdrop-blur-sm transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
        positionClasses[position],
        sizeClasses[size],
        variantClasses[variant]
      ]"
      :title="isScrolling ? 'Scrolling to top...' : 'Scroll to top'"
    >
      <!-- Progress Ring -->
      <div class="absolute inset-0 rounded-full">
        <svg 
          class="h-full w-full -rotate-90 transform" 
          viewBox="0 0 36 36"
        >
          <!-- Background circle -->
          <path
            class="stroke-current opacity-20"
            stroke-width="2"
            fill="none"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <!-- Progress circle -->
          <path
            class="stroke-current transition-all duration-300 ease-out"
            stroke-width="2"
            fill="none"
            stroke-linecap="round"
            :stroke-dasharray="`${scrollProgress}, 100`"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
          />
        </svg>
      </div>

      <!-- Arrow Icon -->
      <div class="relative z-10">
        <Transition
          enter-active-class="transition-all duration-200"
          leave-active-class="transition-all duration-200"
          enter-from-class="opacity-0 rotate-180"
          enter-to-class="opacity-100 rotate-0"
          leave-from-class="opacity-100 rotate-0"
          leave-to-class="opacity-0 rotate-180"
          mode="out-in"
        >
          <svg
            v-if="!isScrolling"
            xmlns="http://www.w3.org/2000/svg"
            :width="size === 'sm' ? 16 : size === 'md' ? 20 : 24"
            :height="size === 'sm' ? 16 : size === 'md' ? 20 : 24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="transition-transform duration-200 group-hover:-translate-y-0.5"
          >
            <path d="m18 15-6-6-6 6"/>
          </svg>
          
          <!-- Loading spinner when scrolling -->
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            :width="size === 'sm' ? 16 : size === 'md' ? 20 : 24"
            :height="size === 'sm' ? 16 : size === 'md' ? 20 : 24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="animate-spin"
          >
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
        </Transition>
      </div>

      <!-- Tooltip -->
      <div class="absolute -top-12 left-1/2 -translate-x-1/2 opacity-0 pointer-events-none transition-opacity duration-200 group-hover:opacity-100">
        <div class="rounded-md bg-popover px-2 py-1 text-xs text-popover-foreground shadow-md border">
          {{ isScrolling ? 'Scrolling...' : 'Back to top' }}
          <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-popover"></div>
        </div>
      </div>
    </button>
  </Transition>
</template>

<style scoped>
/* Additional hover effects */
button:hover .group-hover\:-translate-y-0\.5 {
  transform: translateY(-2px);
}

/* Smooth progress ring animation */
svg path {
  transition: stroke-dasharray 0.3s ease-out;
}

/* Custom focus styles */
button:focus-visible {
  outline: 2px solid hsl(var(--primary));
  outline-offset: 2px;
}
</style>
