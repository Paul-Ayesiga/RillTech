<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import ScrollToTop from '@/components/ui/ScrollToTop.vue';
import { useScrollToTop } from '@/composables/useScrollToTop';

const { scrollToTop, scrollToElement, getScrollPosition, isElementInViewport } = useScrollToTop();

const currentPosition = ref({ x: 0, y: 0 });
const targetElement = ref<HTMLElement>();

const updatePosition = () => {
  currentPosition.value = getScrollPosition();
};

const scrollToTopManual = () => {
  scrollToTop({ smooth: true, duration: 1500 });
};

const scrollToTarget = () => {
  if (targetElement.value) {
    scrollToElement(targetElement.value, { smooth: true, duration: 1000, offset: -100 });
  }
};

const checkInViewport = () => {
  if (targetElement.value) {
    const inView = isElementInViewport(targetElement.value);
    alert(`Target element is ${inView ? 'in' : 'not in'} viewport`);
  }
};

// Update position on scroll
window.addEventListener('scroll', updatePosition, { passive: true });
updatePosition(); // Initial position
</script>

<template>
  <div class="px-3 space-y-6">
    <!-- ScrollToTop Component Demo -->
    <Card>
      <CardHeader>
        <CardTitle>ScrollToTop Component</CardTitle>
        <CardDescription>
          A smooth, animated scroll-to-top button with progress indicator and customizable options.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Features List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold mb-2">Features:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Smooth GSAP-powered scrolling</li>
              <li>Progress ring indicator</li>
              <li>Customizable appearance</li>
              <li>Multiple position options</li>
              <li>Hover effects and animations</li>
              <li>Loading state with spinner</li>
              <li>Accessibility support</li>
              <li>Event callbacks</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-2">Configuration:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Show after: 400px scroll</li>
              <li>Position: Bottom right</li>
              <li>Size: Medium</li>
              <li>Variant: Default (primary)</li>
              <li>Duration: 1200ms</li>
              <li>Smooth scrolling enabled</li>
            </ul>
          </div>
        </div>

        <!-- Demo Controls -->
        <div class="space-y-4">
          <h4 class="font-semibold">Demo Controls:</h4>
          <div class="flex flex-wrap gap-3">
            <Button @click="scrollToTopManual" variant="default">
              Scroll to Top (Manual)
            </Button>
            <Button @click="scrollToTarget" variant="outline">
              Scroll to Target
            </Button>
            <Button @click="checkInViewport" variant="secondary">
              Check Viewport
            </Button>
            <Button @click="updatePosition" variant="ghost">
              Update Position
            </Button>
          </div>
          
          <div class="text-sm text-muted-foreground">
            <p><strong>Current Position:</strong> X: {{ currentPosition.x }}px, Y: {{ currentPosition.y }}px</p>
          </div>
        </div>

        <!-- Scroll Content -->
        <div class="space-y-4">
          <h4 class="font-semibold">Scroll Content (for testing):</h4>
          <div class="space-y-8">
            <div v-for="i in 10" :key="i" class="p-6 border rounded-lg">
              <h5 class="font-medium mb-2">Section {{ i }}</h5>
              <p class="text-muted-foreground">
                This is section {{ i }}. Scroll down to see the ScrollToTop button appear after 400px.
                The button shows a progress ring indicating how far you've scrolled through the page.
                {{ i === 5 ? 'This is the target element for the "Scroll to Target" button.' : '' }}
              </p>
              <div v-if="i === 5" ref="targetElement" class="mt-4 p-4 bg-primary/10 rounded-md">
                <p class="text-sm font-medium">🎯 Target Element</p>
                <p class="text-xs text-muted-foreground">This element is used for the "Scroll to Target" demo.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Usage Examples -->
        <div class="space-y-4">
          <h4 class="font-semibold">Usage Examples:</h4>
          <div class="space-y-3 text-sm">
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">Basic Usage:</p>
              <code class="text-xs">&lt;ScrollToTop /&gt;</code>
            </div>
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">Custom Configuration:</p>
              <code class="text-xs">
                &lt;ScrollToTop :show-after="500" position="bottom-left" size="lg" variant="outline" /&gt;
              </code>
            </div>
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">With Events:</p>
              <code class="text-xs">
                &lt;ScrollToTop @scroll-start="onStart" @scroll-complete="onComplete" /&gt;
              </code>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- ScrollToTop Component (for this demo page) -->
    <ScrollToTop 
      :show-after="200"
      position="bottom-right"
      size="md"
      variant="default"
      :smooth="true"
      :duration="1000"
    />
  </div>
</template>
