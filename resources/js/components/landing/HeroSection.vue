<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

// Refs for animation elements
const heroTitle = ref(null);
const heroSubtitle = ref(null);
const heroCta = ref(null);
const heroImage = ref(null);
const heroGradient = ref(null);

onMounted(() => {
  // Initial entrance animation
  const entranceTl = gsap.timeline();

  // Animate hero elements with split text effect
  const titleSplitText = createSplitText('.hero-title-text');

  entranceTl
    // Animate the background gradient first
    .from('.hero-gradient', {
      opacity: 0,
      scale: 1.5,
      duration: 1.5,
      ease: 'power2.out'
    })
    // Animate each character of the title
    .from(titleSplitText.chars, {
      opacity: 0,
      y: 100,
      rotationX: -90,
      stagger: 0.02,
      duration: 0.8,
      ease: 'back.out(1.7)'
    }, '-=1')
    // Animate the subtitle with a reveal effect
    .from('.hero-subtitle', {
      clipPath: 'polygon(0 0, 0 0, 0 100%, 0% 100%)',
      opacity: 0,
      duration: 1,
      ease: 'power4.out'
    }, '-=0.4')
    // Animate the CTA buttons with a bounce effect
    .from('.hero-cta a', {
      scale: 0,
      opacity: 0,
      stagger: 0.2,
      duration: 0.7,
      ease: 'back.out(1.7)'
    }, '-=0.6')
    // Animate the "Credit card required" callout
    .from('.hero-cta + div div', {
      scale: 0.9,
      opacity: 0,
      duration: 0.5,
      ease: 'back.out(1.7)'
    }, '-=0.3')
    // Animate the enterprise option
    .from('.hero-cta + div + div a', {
      x: -10,
      opacity: 0,
      duration: 0.4,
      ease: 'power2.out'
    }, '-=0.2')
    // Animate the hero image with a reveal effect
    .from('.hero-image', {
      y: 60,
      opacity: 0,
      duration: 1,
      ease: 'power2.out'
    }, '-=0.8')
    // Animate the floating UI elements with a staggered entrance
    .from(['.floating-ui-1', '.floating-ui-2'], {
      scale: 0,
      opacity: 0,
      stagger: 0.2,
      duration: 0.7,
      ease: 'back.out(1.7)'
    }, '-=0.6');

  // Setup scroll-based animations
  setupScrollAnimations();

  // Setup continuous floating animations
  setupFloatingAnimations();
});

// Function to create split text effect
const createSplitText = (selector) => {
  const element = document.querySelector(selector);
  if (!element) return { chars: [] };

  const text = element.textContent;
  element.innerHTML = '';

  const chars = [];

  // Create spans for each character
  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    const span = document.createElement('span');
    span.textContent = char === ' ' ? '\u00A0' : char; // Use non-breaking space for spaces
    span.style.display = 'inline-block';
    span.style.position = 'relative';
    element.appendChild(span);
    chars.push(span);
  }

  return { chars };
};

// Setup scroll-based animations
const setupScrollAnimations = () => {
  // Parallax effect for background elements
  gsap.to('.floating-element-1, .floating-element-2, .floating-element-3', {
    y: (i, el) => -100 - (i * 50),
    scrollTrigger: {
      trigger: '#hero',
      start: 'top top',
      end: 'bottom top',
      scrub: 0.5
    }
  });

  // Parallax for hero image
  gsap.to('.hero-image', {
    y: 100,
    scale: 0.95,
    scrollTrigger: {
      trigger: '#hero',
      start: 'top top',
      end: 'bottom top',
      scrub: 0.5
    }
  });

  // Parallax for hero text
  gsap.to('.hero-title, .hero-subtitle', {
    y: -50,
    scrollTrigger: {
      trigger: '#hero',
      start: 'top top',
      end: 'bottom top',
      scrub: 0.5
    }
  });

  // Fade out effect as user scrolls
  gsap.to('#hero', {
    opacity: 0.5,
    scrollTrigger: {
      trigger: '#hero',
      start: 'center top',
      end: 'bottom top',
      scrub: true
    }
  });
};

// Setup continuous floating animations
const setupFloatingAnimations = () => {
  // Create more complex floating animations for background elements
  gsap.to('.floating-element-1', {
    y: '-=20',
    x: '+=10',
    rotation: 5,
    duration: 3,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true
  });

  gsap.to('.floating-element-2', {
    y: '-=15',
    x: '-=15',
    rotation: -3,
    duration: 4,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.5
  });

  gsap.to('.floating-element-3', {
    y: '-=25',
    x: '+=15',
    rotation: 8,
    duration: 5,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.2
  });

  // Add subtle rotation to the hero image
  gsap.to('.hero-image', {
    rotationY: 3,
    rotationX: 3,
    duration: 6,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true
  });

  // Animate the floating UI elements
  gsap.to('.floating-ui-1', {
    y: -10,
    x: 5,
    rotation: 2,
    duration: 3.5,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true
  });

  gsap.to('.floating-ui-2', {
    y: 10,
    x: -5,
    rotation: -2,
    duration: 4,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.7
  });
};
</script>

<template>
  <section id="hero" class="relative overflow-hidden py-20 md:py-32">
    <!-- Animated background gradient -->
    <div ref="heroGradient" class="hero-gradient absolute inset-0 bg-gradient-to-b from-background via-background/90 to-muted/40"></div>

    <!-- Decorative elements with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="floating-element-1 absolute left-[10%] top-[20%] h-32 w-32 rounded-full bg-primary/15 blur-xl"></div>
      <div class="floating-element-2 absolute right-[15%] top-[30%] h-40 w-40 rounded-full bg-blue-500/15 blur-xl"></div>
      <div class="floating-element-3 absolute bottom-[20%] left-[20%] h-48 w-48 rounded-full bg-purple-500/15 blur-xl"></div>

      <!-- Additional decorative elements for more visual interest -->
      <div class="absolute bottom-[40%] right-[25%] h-24 w-24 rounded-full bg-amber-500/10 blur-xl"></div>
      <div class="absolute left-[30%] top-[15%] h-16 w-16 rounded-full bg-green-500/10 blur-xl"></div>

      <!-- Animated particles -->
      <div class="particles-container absolute inset-0">
        <div v-for="i in 20" :key="i"
             :class="`particle-${i} absolute h-1 w-1 rounded-full bg-primary/30`"
             :style="{
               left: `${Math.random() * 100}%`,
               top: `${Math.random() * 100}%`,
               opacity: Math.random() * 0.5 + 0.3,
               transform: `scale(${Math.random() * 2 + 1})`,
               animationDuration: `${Math.random() * 10 + 10}s`,
               animationDelay: `${Math.random() * 5}s`
             }">
        </div>
      </div>
    </div>

    <div class="container relative mx-auto px-4 md:px-6">
      <div class="grid items-center gap-12 lg:grid-cols-2">
        <!-- Hero content with enhanced animations -->
        <div class="flex flex-col items-center text-center lg:items-start lg:text-left">
          <h1 ref="heroTitle" class="hero-title relative mb-4 text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
            <span class="hero-title-text">Your AI Assistant,</span>
            <span class="relative mt-2 block bg-gradient-to-r from-primary to-purple-500 bg-clip-text text-transparent">
              Assembled in Minutes
              <!-- Animated underline -->
              <span class="absolute bottom-0 left-0 h-1 w-0 bg-gradient-to-r from-primary to-purple-500 animate-expand-width"></span>
            </span>
          </h1>

          <p ref="heroSubtitle" class="hero-subtitle relative mb-8 max-w-md text-xl text-muted-foreground">
            Drag. Drop. Deploy. No code required. Build powerful AI helpers that transform your workflow.
            <!-- Animated highlight -->
            <span class="highlight-text absolute inset-0 bg-primary/5 opacity-0"></span>
          </p>

          <div ref="heroCta" class="hero-cta flex flex-col gap-4 sm:flex-row">
            <a href="#" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-8 py-3 text-lg font-medium text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30">
              Get Started
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
            <a href="#" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-8 py-3 text-lg font-medium text-foreground backdrop-blur-sm transition-all hover:-translate-y-1 hover:bg-accent hover:text-accent-foreground">
              Schedule a Demo
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            </a>
          </div>

          <!-- Credit card required callout -->
          <div class="mt-6 flex items-center">
            <div class="flex items-center rounded-full bg-blue-500/10 px-4 py-1.5 text-sm font-medium text-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 text-blue-500"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
              Credit card required for all plans
            </div>
          </div>

          <!-- Enterprise option -->
          <div class="mt-3">
            <a href="#" class="inline-flex items-center gap-1 text-sm font-medium text-primary underline-offset-4 hover:underline">
              Enterprise options available
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
            </a>
          </div>

          <!-- Scroll indicator -->
          <div class="mt-12 hidden animate-bounce lg:block">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mouse"><rect x="6" y="3" width="12" height="18" rx="6"/><path d="M12 7v4"/></svg>
          </div>
        </div>

        <!-- Hero image with enhanced 3D effect -->
        <div ref="heroImage" class="hero-image relative mx-auto max-w-lg perspective-1000 lg:ml-auto">
          <!-- Main interface mockup with 3D effect -->
          <div class="relative rounded-lg border border-border/50 bg-card/50 p-2 shadow-xl backdrop-blur-sm transition-transform duration-500 hover:rotate-y-5 hover:scale-105">
            <!-- Window controls -->
            <div class="absolute -top-3 left-4 flex items-center gap-1.5">
              <div class="h-3 w-3 rounded-full bg-red-500"></div>
              <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
              <div class="h-3 w-3 rounded-full bg-green-500"></div>
            </div>

            <!-- Interface content -->
            <div class="rounded-md bg-card p-4">
              <div class="grid grid-cols-3 gap-4">
                <!-- Left sidebar -->
                <div class="col-span-1 space-y-3">
                  <div class="h-8 rounded-md bg-muted"></div>
                  <div class="h-24 rounded-md bg-muted"></div>
                  <div class="h-12 rounded-md bg-muted"></div>
                  <div class="h-12 rounded-md bg-muted"></div>
                </div>

                <!-- Main content area -->
                <div class="col-span-2 space-y-3">
                  <div class="h-8 rounded-md bg-muted"></div>
                  <div class="grid grid-cols-2 gap-3">
                    <!-- Animated component -->
                    <div class="h-24 rounded-md bg-primary/20 transition-all duration-700 hover:bg-primary/30"></div>
                    <div class="h-24 rounded-md bg-muted"></div>
                  </div>
                  <div class="h-32 rounded-md bg-muted"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Floating UI elements with enhanced animations -->
          <div class="floating-ui-1 absolute -right-6 -top-6 rounded-lg border border-border bg-card p-3 shadow-lg transition-transform duration-300 hover:scale-105 hover:shadow-xl">
            <div class="flex items-center gap-2">
              <div class="h-8 w-8 rounded-full bg-primary/20"></div>
              <div class="space-y-1">
                <div class="h-2 w-20 rounded-full bg-muted"></div>
                <div class="h-2 w-16 rounded-full bg-muted"></div>
              </div>
            </div>
          </div>

          <div class="floating-ui-2 absolute -bottom-4 -left-6 rounded-lg border border-border bg-card p-3 shadow-lg transition-transform duration-300 hover:scale-105 hover:shadow-xl">
            <div class="space-y-2">
              <div class="h-2 w-24 rounded-full bg-muted"></div>
              <div class="h-2 w-16 rounded-full bg-muted"></div>
            </div>
          </div>

          <!-- Code snippet floating element -->
          <div class="absolute -bottom-2 right-0 hidden rounded-lg border border-border bg-card/90 p-3 shadow-lg backdrop-blur-sm transition-transform duration-300 hover:scale-105 hover:shadow-xl md:block">
            <div class="font-mono text-xs text-muted-foreground">
              <div class="text-primary">function createAgent() {</div>
              <div class="pl-4">return new AI.Agent();</div>
              <div>}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Particle animation */
@keyframes float-up {
  0% { transform: translateY(0) translateX(0); opacity: 0.3; }
  50% { opacity: 0.7; }
  100% { transform: translateY(-100px) translateX(20px); opacity: 0; }
}

[class^="particle-"] {
  animation: float-up linear infinite;
}

/* Expanding width animation for title underline */
@keyframes expandWidth {
  from { width: 0; }
  to { width: 100%; }
}

.animate-expand-width {
  animation: expandWidth 1.5s ease-out forwards;
  animation-delay: 1s;
}

/* 3D perspective for hero image */
.perspective-1000 {
  perspective: 1000px;
}

.rotate-y-5:hover {
  transform: rotateY(5deg);
}
</style>
