<script setup lang="ts">
import DemoScheduleModal from '@/components/modals/DemoScheduleModal.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, ref } from 'vue';

const page = usePage();

// Demo modal state
const isDemoModalOpen = ref(false);

const openDemoModal = () => {
    isDemoModalOpen.value = true;
};

const closeDemoModal = () => {
    isDemoModalOpen.value = false;
};

const handleDemoSuccess = (demoRequest: any) => {
    console.log('Demo scheduled successfully:', demoRequest);
    // Could add additional success handling here
};

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

// Refs for animation elements
const heroTitle = ref(null);
const heroSubtitle = ref(null);
const heroCta = ref(null);
const heroImage = ref(null);
const heroGradient = ref(null);

onMounted(() => {
    // Ensure CTA buttons are visible immediately as fallback
    const ctaButtons = document.querySelectorAll('.hero-cta a') as NodeListOf<HTMLElement>;
    ctaButtons.forEach((button) => {
        button.style.opacity = '1';
        button.style.transform = 'scale(1)';
        button.style.visibility = 'visible';
    });

    // Simple CSS-based animation approach as fallback
    const heroElements = document.querySelectorAll('.hero-title, .hero-subtitle, .hero-cta, .hero-image') as NodeListOf<HTMLElement>;
    heroElements.forEach((element, index) => {
        element.style.opacity = '1';
        element.style.visibility = 'visible';
        element.style.animation = `fadeInUp 0.8s ease-out ${index * 0.2}s both`;
    });

    // Add a small delay to ensure DOM is ready, then try GSAP animations
    setTimeout(() => {
        try {
            // Only run GSAP animations if GSAP is available and working
            if (typeof gsap !== 'undefined') {
                // Simple entrance animation without complex effects
                const entranceTl = gsap.timeline();

                // Simple fade in for background
                gsap.from('.hero-gradient', {
                    opacity: 0,
                    duration: 1,
                    ease: 'power2.out',
                });

                // Simple title animation
                gsap.from('.hero-title', {
                    y: 30,
                    opacity: 0,
                    duration: 0.8,
                    ease: 'power2.out',
                });

                // Simple subtitle animation
                gsap.from('.hero-subtitle', {
                    y: 20,
                    opacity: 0,
                    duration: 0.8,
                    delay: 0.2,
                    ease: 'power2.out',
                });

                // Simple CTA animation - ensure buttons remain visible
                gsap.fromTo(
                    '.hero-cta a',
                    {
                        y: 20,
                        opacity: 0,
                    },
                    {
                        y: 0,
                        opacity: 1,
                        duration: 0.6,
                        delay: 0.4,
                        stagger: 0.1,
                        ease: 'power2.out',
                        onComplete: () => {
                            // Ensure buttons are visible after animation
                            ctaButtons.forEach((button) => {
                                button.style.opacity = '1';
                                button.style.transform = 'scale(1)';
                                button.style.visibility = 'visible';
                            });
                        },
                    },
                );

                // Simple image animation with AI agent reveal
                gsap.fromTo(
                    '.hero-image',
                    {
                        y: 40,
                        opacity: 0,
                    },
                    {
                        y: 0,
                        opacity: 1,
                        duration: 1,
                        delay: 0.6,
                        ease: 'power2.out',
                    },
                );

                // Simple image animation - no scale changes to prevent flickering
                gsap.fromTo(
                    '.hero-image img',
                    {
                        opacity: 0,
                    },
                    {
                        opacity: 1,
                        duration: 1,
                        delay: 0.8,
                        ease: 'power2.out',
                        onComplete: () => {
                            // Ensure image stays visible after animation
                            const img = document.querySelector('.hero-image img') as HTMLElement;
                            if (img) {
                                img.style.opacity = '1';
                                img.style.visibility = 'visible';
                                img.style.display = 'block';
                            }
                        },
                    },
                );

                // Animate the agent status overlay
                gsap.fromTo(
                    '.hero-image .absolute.bottom-4',
                    {
                        y: 20,
                        opacity: 0,
                    },
                    {
                        y: 0,
                        opacity: 1,
                        duration: 0.8,
                        delay: 1.2,
                        ease: 'power2.out',
                    },
                );

                // Setup scroll-based animations (simplified) - temporarily disabled to prevent image flickering
                // setupScrollAnimations();

                // Setup continuous floating animations (simplified)
                setupFloatingAnimations();
            }
        } catch (error) {
            console.error('GSAP animation error:', error);
            // Ensure buttons are visible even if animations fail
            ctaButtons.forEach((button) => {
                button.style.opacity = '1';
                button.style.transform = 'scale(1)';
                button.style.visibility = 'visible';
            });
        }
    }, 100);
});

// Image handling functions
const handleImageError = (event: Event) => {
    console.warn('AI agent image failed to load, showing fallback');
    const img = event.target as HTMLImageElement;
    const container = img.parentElement;
    if (container) {
        container.innerHTML = `
      <div class="flex h-full items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5">
        <div class="text-center">
          <div class="mx-auto mb-4 h-24 w-24 rounded-full bg-primary/10 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path d="M2 17l10 5 10-5"/>
              <path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-foreground">AI Agent</h3>
          <p class="text-sm text-muted-foreground">Your intelligent assistant</p>
        </div>
      </div>
    `;
    }
};

const handleImageLoad = () => {
    console.log('AI agent image loaded successfully');

    // Ensure image stays visible after load
    setTimeout(() => {
        const img = document.querySelector('.hero-image img') as HTMLElement;
        if (img) {
            img.style.opacity = '1';
            img.style.transform = 'scale(1)';
            img.style.visibility = 'visible';
            img.style.display = 'block';
        }
    }, 100);

    // Set up a periodic check to ensure image stays visible
    const checkImageVisibility = () => {
        const img = document.querySelector('.hero-image img') as HTMLElement;
        if (img && (img.style.opacity === '0' || img.style.visibility === 'hidden')) {
            img.style.opacity = '1';
            img.style.transform = 'scale(1)';
            img.style.visibility = 'visible';
            img.style.display = 'block';
        }
    };

    // Check every 500ms for the first 5 seconds
    const interval = setInterval(checkImageVisibility, 500);
    setTimeout(() => clearInterval(interval), 5000);
};

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

// Setup scroll-based animations (optimized)
const setupScrollAnimations = () => {
    // Reduced parallax effect for background elements (less intensive)
    gsap.to('.floating-element-1, .floating-element-2, .floating-element-3', {
        y: (i, el) => -50 - i * 25, // Reduced movement
        scrollTrigger: {
            trigger: '#hero',
            start: 'top top',
            end: 'bottom top',
            scrub: 1, // Slower scrub for better performance
            invalidateOnRefresh: true,
        },
    });

    // Simplified parallax for hero image
    gsap.to('.hero-image', {
        y: 50, // Reduced movement
        scrollTrigger: {
            trigger: '#hero',
            start: 'top top',
            end: 'bottom top',
            scrub: 1,
            invalidateOnRefresh: true,
        },
    });

    // Removed parallax for hero text to prevent CTA button issues
    // Removed fade out effect to prevent CTA button visibility issues
};

// Setup continuous floating animations (optimized)
const setupFloatingAnimations = () => {
    // Simplified floating animations for background elements (reduced memory usage)
    gsap.to('.floating-element-1', {
        y: '-=10', // Reduced movement
        duration: 4, // Slower animation
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
    });

    gsap.to('.floating-element-2', {
        y: '-=8', // Reduced movement
        duration: 5, // Slower animation
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
        delay: 1,
    });

    gsap.to('.floating-element-3', {
        y: '-=12', // Reduced movement
        duration: 6, // Slower animation
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
        delay: 0.5,
    });

    // Removed complex 3D rotations for hero image to improve performance

    // Simplified floating UI elements
    gsap.to('.floating-ui-1', {
        y: -5, // Reduced movement
        duration: 4,
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
    });

    gsap.to('.floating-ui-2', {
        y: 5, // Reduced movement
        duration: 5,
        ease: 'sine.inOut',
        repeat: -1,
        yoyo: true,
        delay: 1,
    });
};
</script>

<template>
    <section id="hero" class="relative overflow-hidden py-20 md:py-32">
        <!-- Animated background gradient -->
        <div ref="heroGradient" class="hero-gradient from-background via-background/90 to-muted/40 absolute inset-0 bg-gradient-to-b"></div>

        <!-- Decorative elements with enhanced animations -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="floating-element-1 bg-primary/15 absolute top-[20%] left-[10%] h-32 w-32 rounded-full blur-xl"></div>
            <div class="floating-element-2 absolute top-[30%] right-[15%] h-40 w-40 rounded-full bg-blue-500/15 blur-xl"></div>
            <div class="floating-element-3 absolute bottom-[20%] left-[20%] h-48 w-48 rounded-full bg-purple-500/15 blur-xl"></div>

            <!-- Additional decorative elements for more visual interest -->
            <div class="absolute right-[25%] bottom-[40%] h-24 w-24 rounded-full bg-amber-500/10 blur-xl"></div>
            <div class="absolute top-[15%] left-[30%] h-16 w-16 rounded-full bg-green-500/10 blur-xl"></div>

            <!-- Animated particles -->
            <div class="particles-container absolute inset-0">
                <div
                    v-for="i in 20"
                    :key="i"
                    :class="`particle-${i} bg-primary/30 absolute h-1 w-1 rounded-full`"
                    :style="{
                        left: `${Math.random() * 100}%`,
                        top: `${Math.random() * 100}%`,
                        opacity: Math.random() * 0.5 + 0.3,
                        transform: `scale(${Math.random() * 2 + 1})`,
                        animationDuration: `${Math.random() * 10 + 10}s`,
                        animationDelay: `${Math.random() * 5}s`,
                    }"
                ></div>
            </div>
        </div>

        <div class="relative container mx-auto px-4 md:px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <!-- Hero content with enhanced animations -->
                <div class="flex flex-col items-center text-center lg:items-start lg:text-left">
                    <h1 ref="heroTitle" class="hero-title relative mb-4 text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
                        <span class="hero-title-text">Your AI Assistant,</span>
                        <span class="from-primary relative mt-2 block bg-gradient-to-r to-purple-500 bg-clip-text text-transparent">
                            Assembled in Minutes
                            <!-- Animated underline -->
                            <span class="from-primary animate-expand-width absolute bottom-0 left-0 h-1 w-0 bg-gradient-to-r to-purple-500"></span>
                        </span>
                    </h1>

                    <p ref="heroSubtitle" class="hero-subtitle text-muted-foreground relative mb-8 max-w-md text-xl">
                        Drag. Drop. Deploy. No code required. Build powerful AI helpers that transform your workflow.
                        <!-- Animated highlight -->
                        <span class="highlight-text bg-primary/5 absolute inset-0 opacity-0"></span>
                    </p>

                    <div ref="heroCta" class="hero-cta relative z-10 flex flex-col gap-4 sm:flex-row">
                        <Link
                            :href="page.props.auth.user ? route('subscriptions.index') : route('register')"
                            class="cta-button bg-primary text-primary-foreground shadow-primary/20 hover:shadow-primary/30 relative z-10 inline-flex items-center justify-center gap-2 rounded-md px-8 py-3 text-lg font-medium shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl"
                        >
                            Get Started
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
                                class="lucide lucide-arrow-right"
                            >
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </Link>
                        <button
                            @click="openDemoModal"
                            class="cta-button border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground relative z-10 inline-flex items-center justify-center gap-2 rounded-md border px-8 py-3 text-lg font-medium backdrop-blur-sm transition-all hover:-translate-y-1"
                        >
                            Schedule a Demo
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
                                class="lucide lucide-calendar"
                            >
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                <line x1="16" x2="16" y1="2" y2="6" />
                                <line x1="8" x2="8" y1="2" y2="6" />
                                <line x1="3" x2="21" y1="10" y2="10" />
                            </svg>
                        </button>
                    </div>

                    <!-- Credit card required callout -->
                    <div class="mt-6 flex items-center">
                        <div class="flex items-center rounded-full bg-blue-500/10 px-4 py-1.5 text-sm font-medium text-blue-600">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mr-1.5 text-blue-500"
                            >
                                <rect width="20" height="14" x="2" y="5" rx="2" />
                                <line x1="2" x2="22" y1="10" y2="10" />
                            </svg>
                            Credit card required for all plans
                        </div>
                    </div>

                    <!-- Enterprise option -->
                    <div class="mt-3">
                        <a href="#" class="text-primary inline-flex items-center gap-1 text-sm font-medium underline-offset-4 hover:underline">
                            Enterprise options available
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-external-link"
                            >
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                <polyline points="15 3 21 3 21 9" />
                                <line x1="10" x2="21" y1="14" y2="3" />
                            </svg>
                        </a>
                    </div>

                    <!-- Scroll indicator -->
                    <div class="mt-12 hidden animate-bounce lg:block">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-mouse"
                        >
                            <rect x="6" y="3" width="12" height="18" rx="6" />
                            <path d="M12 7v4" />
                        </svg>
                    </div>
                </div>

                <!-- Hero image with AI Agent -->
                <div ref="heroImage" class="hero-image perspective-1000 relative mx-auto max-w-lg lg:ml-auto">
                    <!-- AI Agent Image Container -->
                    <div
                        class="from-primary/20 via-primary/10 to-background/80 relative aspect-square overflow-hidden rounded-2xl bg-gradient-to-br p-4 shadow-2xl backdrop-blur-sm transition-transform duration-500 hover:scale-105"
                    >
                        <!-- Background gradient overlays for better integration -->
                        <div class="from-background/60 absolute inset-0 bg-gradient-to-t via-transparent to-transparent"></div>
                        <div class="from-primary/5 to-primary/10 absolute inset-0 bg-gradient-to-r via-transparent"></div>
                        <div class="via-primary/5 to-background/40 absolute inset-0 bg-gradient-to-br from-transparent"></div>

                        <!-- AI Agent Image -->
                        <div class="relative h-full w-full overflow-hidden rounded-xl">
                            <img
                                src="/images/ai2.jpg"
                                alt="AI Agent Assistant"
                                class="h-full w-full object-cover object-center transition-all duration-700 hover:scale-110"
                                style="filter: contrast(1.1) brightness(1.05) saturate(0.9) hue-rotate(5deg)"
                                loading="lazy"
                                @error="handleImageError"
                                @load="handleImageLoad"
                            />

                            <!-- Subtle color overlay to match theme -->
                            <div class="from-background/50 via-primary/5 absolute inset-0 bg-gradient-to-t to-transparent mix-blend-overlay"></div>
                            <div
                                class="from-primary/10 to-background/30 absolute inset-0 bg-gradient-to-br via-transparent mix-blend-soft-light"
                            ></div>

                            <!-- Agent status overlay -->
                            <div class="absolute right-4 bottom-4 left-4">
                                <div
                                    class="bg-background/90 border-border/50 hover:bg-background/95 rounded-lg border p-3 shadow-lg backdrop-blur-md transition-all duration-300"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="bg-primary/20 ring-primary/30 flex h-8 w-8 items-center justify-center rounded-full ring-2">
                                            <div class="h-2 w-2 animate-pulse rounded-full bg-green-500 shadow-lg shadow-green-500/50"></div>
                                        </div>
                                        <div>
                                            <h3 class="text-foreground text-sm font-semibold">AI Agent Online</h3>
                                            <p class="text-muted-foreground text-xs">Ready to assist you</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="flex items-center gap-1">
                                                <div class="bg-primary h-1 w-1 animate-pulse rounded-full"></div>
                                                <div class="bg-primary h-1 w-1 animate-pulse rounded-full" style="animation-delay: 0.2s"></div>
                                                <div class="bg-primary h-1 w-1 animate-pulse rounded-full" style="animation-delay: 0.4s"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating UI elements with enhanced animations -->
                    <div
                        class="floating-ui-1 border-border bg-card absolute -top-6 -right-6 rounded-lg border p-3 shadow-lg transition-transform duration-300 hover:scale-105 hover:shadow-xl"
                    >
                        <div class="flex items-center gap-2">
                            <div class="bg-primary/20 h-8 w-8 rounded-full"></div>
                            <div class="space-y-1">
                                <div class="bg-muted h-2 w-20 rounded-full"></div>
                                <div class="bg-muted h-2 w-16 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="floating-ui-2 border-border bg-card absolute -bottom-4 -left-6 rounded-lg border p-3 shadow-lg transition-transform duration-300 hover:scale-105 hover:shadow-xl"
                    >
                        <div class="space-y-2">
                            <div class="bg-muted h-2 w-24 rounded-full"></div>
                            <div class="bg-muted h-2 w-16 rounded-full"></div>
                        </div>
                    </div>

                    <!-- Code snippet floating element -->
                    <div
                        class="border-border bg-card/95 absolute right-0 -bottom-2 hidden rounded-lg border p-4 shadow-lg backdrop-blur-sm transition-transform duration-300 hover:scale-105 hover:shadow-xl md:block"
                    >
                        <div class="text-muted-foreground font-mono text-xs">
                            <div class="text-green-500">// Create AI Agent</div>
                            <div class="text-primary">const agent = new AI.Agent({</div>
                            <div class="pl-4 text-blue-400">model: 'gpt-4',</div>
                            <div class="pl-4 text-blue-400">capabilities: ['chat', 'analyze']</div>
                            <div class="text-primary">});</div>
                            <div class="text-green-500">// Ready to assist! 🤖</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demo Schedule Modal -->
        <DemoScheduleModal v-model:open="isDemoModalOpen" @success="handleDemoSuccess" />
    </section>
</template>

<style scoped>
/* Ensure CTA buttons are always visible as fallback */
.hero-cta a,
.cta-button {
    opacity: 1 !important;
    transform: scale(1) !important;
    visibility: visible !important;
    display: inline-flex !important;
    pointer-events: auto !important;
}

/* Particle animation */
@keyframes float-up {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0.3;
    }
    50% {
        opacity: 0.7;
    }
    100% {
        transform: translateY(-100px) translateX(20px);
        opacity: 0;
    }
}

[class^='particle-'] {
    animation: float-up linear infinite;
}

/* Expanding width animation for title underline */
@keyframes expandWidth {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
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

/* Ensure all hero content is visible */
.hero-title,
.hero-subtitle,
.hero-cta,
.hero-image {
    opacity: 1;
    visibility: visible;
}

/* CSS fallback animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* AI Agent image enhancements - Force visibility */
.hero-image img {
    transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1) !important;
    object-position: center center !important;
    min-height: 100% !important;
    min-width: 100% !important;
    opacity: 1 !important;
    visibility: visible !important;
    display: block !important;
    transform: scale(1) !important;
}

.hero-image:hover img {
    filter: contrast(1.15) brightness(1.1) saturate(1) hue-rotate(8deg) !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Ensure proper aspect ratio */
.hero-image .aspect-square {
    aspect-ratio: 1 / 1;
}

/* Force image container to be visible */
.hero-image .relative.h-full.w-full {
    opacity: 1 !important;
    visibility: visible !important;
}

/* Enhanced gradient overlays */
.hero-image .absolute[class*='bg-gradient'] {
    transition: opacity 0.5s ease-in-out;
}

.hero-image:hover .absolute[class*='bg-gradient'] {
    opacity: 0.8;
}

/* Agent status indicator animation */
@keyframes pulse-glow {
    0%,
    100% {
        box-shadow: 0 0 5px rgba(34, 197, 94, 0.5);
    }
    50% {
        box-shadow:
            0 0 20px rgba(34, 197, 94, 0.8),
            0 0 30px rgba(34, 197, 94, 0.4);
    }
}

.hero-image .bg-green-500 {
    animation: pulse-glow 2s ease-in-out infinite;
}
</style>
