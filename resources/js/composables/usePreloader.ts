import { ref, readonly, nextTick } from 'vue';

const isLoading = ref(true);
const loadingProgress = ref(0);

export function usePreloader() {
  const show = () => {
    isLoading.value = true;
    loadingProgress.value = 0;
  };

  const hide = () => {
    isLoading.value = false;
  };

  const setProgress = (progress: number) => {
    loadingProgress.value = Math.max(0, Math.min(100, progress));
  };

  const simulateLoading = async (duration: number = 2000) => {
    show();

    return new Promise<void>((resolve) => {
      const startTime = Date.now();
      const interval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(100, (elapsed / duration) * 100);

        setProgress(progress);

        if (progress >= 100) {
          clearInterval(interval);
          setTimeout(() => {
            hide();
            resolve();
          }, 300);
        }
      }, 50);
    });
  };

  const waitForPageLoad = () => {
    return new Promise<void>((resolve) => {
      if (document.readyState === 'complete') {
        resolve();
      } else {
        window.addEventListener('load', () => resolve(), { once: true });
      }
    });
  };

  const initializeApp = async () => {
    show();

    // Wait for page to load
    await waitForPageLoad();

    // Simulate minimum loading time for better UX
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Ensure smooth transition
    await nextTick();

    hide();
  };

  return {
    isLoading: readonly(isLoading),
    loadingProgress: readonly(loadingProgress),
    show,
    hide,
    setProgress,
    simulateLoading,
    initializeApp
  };
}

// Create a global instance
export const preloader = usePreloader();
