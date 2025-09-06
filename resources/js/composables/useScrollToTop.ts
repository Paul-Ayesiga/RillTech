import { ref, readonly, onMounted, onUnmounted } from 'vue';
import { gsap } from 'gsap';

export function useScrollToTop() {
  const isVisible = ref(false);
  const isScrolling = ref(false);
  const scrollProgress = ref(0);

  let ticking = false;

  // Calculate scroll progress and visibility
  const updateScrollState = (showAfter: number = 300) => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
    const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;

    scrollProgress.value = Math.min(100, Math.max(0, progress));
    isVisible.value = scrollTop > showAfter;
  };

  // Throttled scroll handler
  const createScrollHandler = (showAfter: number = 300) => {
    return () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          updateScrollState(showAfter);
          ticking = false;
        });
        ticking = true;
      }
    };
  };

  // Smooth scroll to top function
  const scrollToTop = async (options: {
    smooth?: boolean;
    duration?: number;
    offset?: number;
  } = {}) => {
    const { smooth = true, duration = 1000, offset = 0 } = options;

    if (isScrolling.value) return;

    isScrolling.value = true;

    return new Promise<void>((resolve) => {
      if (smooth && typeof gsap !== 'undefined') {
        // Use GSAP for smooth scrolling
        gsap.to(window, {
          duration: duration / 1000,
          scrollTo: { y: offset, autoKill: false },
          ease: 'power2.out',
          onComplete: () => {
            isScrolling.value = false;
            resolve();
          }
        });
      } else {
        // Fallback to native smooth scrolling
        window.scrollTo({
          top: offset,
          behavior: smooth ? 'smooth' : 'auto'
        });

        // Estimate completion time for native smooth scroll
        setTimeout(() => {
          isScrolling.value = false;
          resolve();
        }, duration);
      }
    });
  };

  // Scroll to specific element
  const scrollToElement = async (
    selector: string | HTMLElement,
    options: {
      smooth?: boolean;
      duration?: number;
      offset?: number;
    } = {}
  ) => {
    const { smooth = true, duration = 1000, offset = 0 } = options;

    const element = typeof selector === 'string'
      ? document.querySelector(selector) as HTMLElement
      : selector;

    if (!element) {
      console.warn(`Element not found: ${selector}`);
      return;
    }

    if (isScrolling.value) return;

    isScrolling.value = true;

    return new Promise<void>((resolve) => {
      const elementTop = element.offsetTop + offset;

      if (smooth && typeof gsap !== 'undefined') {
        gsap.to(window, {
          duration: duration / 1000,
          scrollTo: { y: elementTop, autoKill: false },
          ease: 'power2.out',
          onComplete: () => {
            isScrolling.value = false;
            resolve();
          }
        });
      } else {
        window.scrollTo({
          top: elementTop,
          behavior: smooth ? 'smooth' : 'auto'
        });

        setTimeout(() => {
          isScrolling.value = false;
          resolve();
        }, duration);
      }
    });
  };

  // Get current scroll position
  const getScrollPosition = () => {
    return {
      x: window.pageXOffset || document.documentElement.scrollLeft,
      y: window.pageYOffset || document.documentElement.scrollTop
    };
  };

  // Check if element is in viewport
  const isElementInViewport = (element: HTMLElement, threshold: number = 0) => {
    const rect = element.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    const windowWidth = window.innerWidth || document.documentElement.clientWidth;

    return (
      rect.top >= -threshold &&
      rect.left >= -threshold &&
      rect.bottom <= windowHeight + threshold &&
      rect.right <= windowWidth + threshold
    );
  };

  // Setup scroll tracking
  const setupScrollTracking = (showAfter: number = 300) => {
    const scrollHandler = createScrollHandler(showAfter);

    onMounted(() => {
      window.addEventListener('scroll', scrollHandler, { passive: true });
      updateScrollState(showAfter); // Initial check
    });

    onUnmounted(() => {
      window.removeEventListener('scroll', scrollHandler);
    });

    return scrollHandler;
  };

  return {
    // Reactive state
    isVisible: readonly(isVisible),
    isScrolling: readonly(isScrolling),
    scrollProgress: readonly(scrollProgress),

    // Methods
    scrollToTop,
    scrollToElement,
    getScrollPosition,
    isElementInViewport,
    setupScrollTracking,
    updateScrollState
  };
}

// Create a global instance
export const scrollToTop = useScrollToTop();
