<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { X, Play, Pause, Volume2, VolumeX, Maximize, Minimize } from 'lucide-vue-next';

interface Props {
  open: boolean;
  videoUrl?: string;
  title?: string;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  videoUrl: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
  title: 'Product Demo Video'
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  close: [];
}>();

const videoRef = ref<HTMLVideoElement>();
const isPlaying = ref(false);
const isMuted = ref(false);
const isFullscreen = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const isLoading = ref(true);
const hasError = ref(false);

const isOpen = computed({
  get: () => props.open,
  set: (value: boolean) => {
    emit('update:open', value);
    if (!value) {
      emit('close');
    }
  }
});

const formatTime = (time: number): string => {
  const minutes = Math.floor(time / 60);
  const seconds = Math.floor(time % 60);
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

const togglePlay = () => {
  if (!videoRef.value) return;
  
  if (isPlaying.value) {
    videoRef.value.pause();
  } else {
    videoRef.value.play();
  }
};

const toggleMute = () => {
  if (!videoRef.value) return;
  
  videoRef.value.muted = !videoRef.value.muted;
  isMuted.value = videoRef.value.muted;
};

const toggleFullscreen = () => {
  if (!videoRef.value) return;
  
  if (!isFullscreen.value) {
    if (videoRef.value.requestFullscreen) {
      videoRef.value.requestFullscreen();
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
};

const handleTimeUpdate = () => {
  if (videoRef.value) {
    currentTime.value = videoRef.value.currentTime;
  }
};

const handleLoadedMetadata = () => {
  if (videoRef.value) {
    duration.value = videoRef.value.duration;
    isLoading.value = false;
  }
};

const handlePlay = () => {
  isPlaying.value = true;
};

const handlePause = () => {
  isPlaying.value = false;
};

const handleError = () => {
  hasError.value = true;
  isLoading.value = false;
};

const handleSeek = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const seekTime = (parseFloat(target.value) / 100) * duration.value;
  
  if (videoRef.value) {
    videoRef.value.currentTime = seekTime;
  }
};

const progressPercentage = computed(() => {
  if (duration.value === 0) return 0;
  return (currentTime.value / duration.value) * 100;
});

const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement;
};

// Reset video when modal closes
watch(isOpen, (newValue) => {
  if (!newValue && videoRef.value) {
    videoRef.value.pause();
    videoRef.value.currentTime = 0;
    isPlaying.value = false;
    hasError.value = false;
    isLoading.value = true;
  }
});

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-4xl p-0 overflow-hidden">
      <DialogHeader class="px-6 py-4 border-b">
        <DialogTitle class="text-xl font-semibold">{{ title }}</DialogTitle>
      </DialogHeader>

      <div class="relative bg-black">
        <!-- Loading State -->
        <div 
          v-if="isLoading && !hasError" 
          class="absolute inset-0 flex items-center justify-center bg-black/50 z-10"
        >
          <div class="flex flex-col items-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
            <p class="text-white text-sm">Loading video...</p>
          </div>
        </div>

        <!-- Error State -->
        <div 
          v-if="hasError" 
          class="absolute inset-0 flex items-center justify-center bg-black z-10"
        >
          <div class="flex flex-col items-center space-y-4 text-center px-6">
            <div class="h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
              <X class="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>
            <div>
              <h3 class="text-white font-medium mb-2">Video Unavailable</h3>
              <p class="text-gray-300 text-sm">
                Sorry, we couldn't load the demo video. Please try again later or contact support.
              </p>
            </div>
          </div>
        </div>

        <!-- Video Element -->
        <video
          ref="videoRef"
          :src="videoUrl"
          class="w-full aspect-video"
          @timeupdate="handleTimeUpdate"
          @loadedmetadata="handleLoadedMetadata"
          @play="handlePlay"
          @pause="handlePause"
          @error="handleError"
          preload="metadata"
        />

        <!-- Video Controls -->
        <div 
          v-if="!isLoading && !hasError"
          class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4"
        >
          <!-- Progress Bar -->
          <div class="mb-4">
            <input
              type="range"
              min="0"
              max="100"
              :value="progressPercentage"
              @input="handleSeek"
              class="w-full h-1 bg-white/30 rounded-lg appearance-none cursor-pointer slider"
            />
          </div>

          <!-- Control Buttons -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <!-- Play/Pause Button -->
              <Button
                variant="ghost"
                size="icon"
                @click="togglePlay"
                class="text-white hover:bg-white/20 h-10 w-10"
              >
                <Play v-if="!isPlaying" class="h-5 w-5" />
                <Pause v-else class="h-5 w-5" />
              </Button>

              <!-- Mute Button -->
              <Button
                variant="ghost"
                size="icon"
                @click="toggleMute"
                class="text-white hover:bg-white/20 h-10 w-10"
              >
                <Volume2 v-if="!isMuted" class="h-5 w-5" />
                <VolumeX v-else class="h-5 w-5" />
              </Button>

              <!-- Time Display -->
              <div class="text-white text-sm font-medium">
                {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
              </div>
            </div>

            <!-- Fullscreen Button -->
            <Button
              variant="ghost"
              size="icon"
              @click="toggleFullscreen"
              class="text-white hover:bg-white/20 h-10 w-10"
            >
              <Maximize v-if="!isFullscreen" class="h-5 w-5" />
              <Minimize v-else class="h-5 w-5" />
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* Custom slider styles */
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: #ffffff;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: #ffffff;
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
