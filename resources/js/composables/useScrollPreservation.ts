import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

interface ScrollPosition {
  [key: string]: number;
}

export function useScrollPreservation() {
  const scrollPositions: ScrollPosition = {};

  const saveScrollPositions = () => {
    // Save scroll positions for elements with scroll-region attribute
    const scrollRegions = document.querySelectorAll('[scroll-region]');
    scrollRegions.forEach((element, index) => {
      const key = element.getAttribute('data-scroll-key') || `scroll-region-${index}`;
      scrollPositions[key] = element.scrollTop;
    });
  };

  const restoreScrollPositions = () => {
    // Restore scroll positions after a short delay to ensure DOM is ready
    setTimeout(() => {
      const scrollRegions = document.querySelectorAll('[scroll-region]');
      scrollRegions.forEach((element, index) => {
        const key = element.getAttribute('data-scroll-key') || `scroll-region-${index}`;
        if (scrollPositions[key] !== undefined) {
          element.scrollTop = scrollPositions[key];
        }
      });
    }, 50);
  };

  const setupScrollPreservation = () => {
    // Save scroll positions before navigation
    const removeStartListener = router.on('start', () => {
      saveScrollPositions();
    });

    // Restore scroll positions after navigation
    const removeFinishListener = router.on('finish', () => {
      restoreScrollPositions();
    });

    // Cleanup listeners
    onUnmounted(() => {
      removeStartListener();
      removeFinishListener();
    });
  };

  onMounted(() => {
    setupScrollPreservation();
  });

  return {
    saveScrollPositions,
    restoreScrollPositions,
    setupScrollPreservation
  };
}
