<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { Button } from '@/components/ui/button';
import Preloader from '@/components/ui/Preloader.vue';
import ScrollToTop from '@/components/ui/ScrollToTop.vue';
import ChatWidget from '@/components/ui/ChatWidget.vue';
import { preloader } from '@/composables/usePreloader';
import HeroSection from '@/components/landing/HeroSection.vue';
import HowItWorksSection from '@/components/landing/HowItWorksSection.vue';
import FeaturesSection from '@/components/landing/FeaturesSection.vue';
import SocialProofSection from '@/components/landing/SocialProofSection.vue';
import DemoSection from '@/components/landing/DemoSection.vue';
import PricingSection from '@/components/landing/PricingSection.vue';
import TalkToUsSection from '@/components/landing/TalkToUsSection.vue';
import FooterSection from '@/components/landing/FooterSection.vue';
import { Toaster } from '@/components/ui/sonner'

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

// Track active section for navigation
const activeSection = ref('hero');
const sections = ['hero', 'how-it-works', 'features', 'social-proof', 'demo', 'pricing', 'talk-to-us', 'newsletter'];
const navItems = [
  { id: 'features', label: 'Features' },
  { id: 'how-it-works', label: 'How It Works' },
  { id: 'demo', label: 'Demo' },
  { id: 'pricing', label: 'Pricing' },
  { id: 'talk-to-us', label: 'Contact' }
];

// Mobile menu state
const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};

onMounted(async () => {
  // Initialize preloader for landing page
  preloader.show();
  preloader.setProgress(10);

  // Add landing page class to HTML element for global styles
  document.documentElement.classList.add('landing-page-active');
  preloader.setProgress(20);

  // Wait for page assets to load
  await new Promise(resolve => {
    if (document.readyState === 'complete') {
      preloader.setProgress(50);
      setTimeout(resolve, 800);
    } else {
      window.addEventListener('load', () => {
        preloader.setProgress(50);
        setTimeout(resolve, 800);
      }, { once: true });
    }
  });

  // Initialize GSAP animations
  preloader.setProgress(70);
  initAnimations();

  // Setup scroll tracking for active section
  preloader.setProgress(85);
  setupScrollTracking();

  // Animate the navbar
  preloader.setProgress(95);
  animateNavbar();

  // Final loading step
  preloader.setProgress(100);

  // Small delay before hiding for smooth UX
  await new Promise(resolve => setTimeout(resolve, 500));

  // Hide preloader after everything is ready
  preloader.hide();

  // Clean up when component is unmounted
  onUnmounted(() => {
    document.documentElement.classList.remove('landing-page-active');
  });
});

const setupScrollTracking = () => {
  sections.forEach(section => {
    ScrollTrigger.create({
      trigger: `#${section}`,
      start: 'top 50%',
      end: 'bottom 50%',
      onEnter: () => { activeSection.value = section; },
      onEnterBack: () => { activeSection.value = section; }
    });
  });
};

const animateNavbar = () => {
  // Animate navbar appearing
  gsap.from('.floating-navbar', {
    y: -50,
    opacity: 0,
    duration: 1,
    ease: 'power3.out',
    delay: 0.5
  });

  // Add scroll animation for navbar
  gsap.to('.floating-navbar', {
    scrollTrigger: {
      trigger: 'body',
      start: 'top top',
      end: '+=500',
      scrub: true
    },
    padding: '0.5rem 1rem',
    duration: 0.5
  });
};

const initAnimations = () => {
  // Stagger animations for sections as they come into view
  gsap.utils.toArray('.animate-section').forEach((section: any) => {
    gsap.from(section, {
      y: 50,
      opacity: 0,
      duration: 0.8,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: section,
        start: 'top 80%',
        toggleActions: 'play none none none'
      }
    });
  });
};

// Enhanced smooth scrolling function using GSAP
const scrollToSection = (sectionId: string) => {
  mobileMenuOpen.value = false; // Close mobile menu if open
  const element = document.getElementById(sectionId);
  if (element) {
    // Get the element's position
    const headerOffset = 100; // Account for floating header

    // Use GSAP's ScrollToPlugin for smoother animation
    gsap.to(window, {
      duration: 1.2,
      scrollTo: {
        y: element,
        offsetY: headerOffset,
        autoKill: false
      },
      ease: 'power3.inOut'
    });

    // Add a subtle page-wide animation when scrolling
    gsap.to('.floating-navbar', {
      duration: 0.4,
      yPercent: -5,
      ease: 'power1.out',
      yoyo: true,
      repeat: 1
    });
  }
};

// Scroll to top event handlers
const onScrollStart = () => {
  console.log('Scrolling to top started');
};

const onScrollComplete = () => {
  console.log('Scrolling to top completed');
};

// Chat event handlers
const onChatMessageSent = (message: string) => {
  console.log('Message sent:', message);
};

const onChatOpened = () => {
  console.log('Chat opened');
};

const onChatClosed = () => {
  console.log('Chat closed');
};

// Page props
const page = usePage();
</script>

<template>
    <Toaster />

    <Head title="Build AI Agents in Minutes">
        <meta name="description"
            content="Create powerful AI assistants with our no-code drag-and-drop interface. Build, train, and deploy in minutes, not months." />
    </Head>

    <!-- Preloader -->
    <Preloader :show="preloader.isLoading.value" logo-text="RillTech" loading-text="Building your AI experience..."
        :duration="0" @loaded="preloader.hide" />

    <div class="landing-page flex min-h-screen w-full flex-col bg-background text-foreground">
        <!-- Navigation -->
        <header class="fixed top-0 z-50 w-full">
            <div class="container flex h-24 items-center justify-center px-4 md:px-6">
                <!-- Centered floating navbar with logo and auth buttons -->
                <div
                    class="floating-navbar flex w-full max-w-6xl items-center justify-between rounded-full border border-border/40 bg-background/90 px-4 py-2 shadow-lg backdrop-blur-md">
                    <!-- Logo (left side) -->
                    <div class="flex items-center gap-2">
                        <img src="/images/logo.png" alt="RillTech Logo" class="h-8 w-auto" />
                        <span class="text-xl font-bold">RillTech</span>
                    </div>

                    <!-- Desktop Navigation (center) -->
                    <nav class="hidden md:flex items-center">
                        <div class="flex rounded-full bg-muted/50 p-1">
                            <button v-for="item in navItems" :key="item.id" @click="scrollToSection(item.id)" :class="[
                  'relative px-4 py-2 text-sm font-medium transition-all duration-300',
                  activeSection === item.id
                    ? 'text-primary-foreground'
                    : 'text-foreground/80 hover:text-primary'
                ]">
                                {{ item.label }}
                                <!-- Active indicator pill -->
                                <span v-if="activeSection === item.id"
                                    class="absolute inset-0 z-[-1] rounded-full bg-primary transition-all duration-300"></span>
                            </button>
                        </div>
                    </nav>

                    <!-- Mobile menu button (center for mobile) -->
                    <button @click="toggleMobileMenu"
                        class="flex h-10 w-10 items-center justify-center rounded-full md:hidden">
                        <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu">
                            <line x1="4" x2="20" y1="12" y2="12" />
                            <line x1="4" x2="20" y1="6" y2="6" />
                            <line x1="4" x2="20" y1="18" y2="18" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-x">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>

                    <!-- Auth buttons (right side) -->
                    <div class="hidden items-center gap-4 sm:flex">
                        <Link v-if="page.props.auth.user" :href="route('dashboard')">
                        <Button variant="default" size="sm" class="rounded-full">Dashboard</Button>
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium hover:text-primary">Log in</Link>
                            <Link :href="route('register')">
                            <Button variant="default" size="sm" class="rounded-full">Sign up free</Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Mobile menu dropdown -->
            <div v-if="mobileMenuOpen" class="container animate-in fade-in slide-in-from-top-5 duration-300 md:hidden">
                <div class="rounded-lg border border-border/40 bg-background/95 p-4 shadow-lg backdrop-blur-md">
                    <nav class="flex flex-col space-y-3">
                        <button v-for="item in navItems" :key="item.id" @click="scrollToSection(item.id)" :class="[
                'flex items-center justify-between rounded-md px-4 py-2 text-sm font-medium transition-colors',
                activeSection === item.id
                  ? 'bg-primary text-primary-foreground'
                  : 'hover:bg-muted'
              ]">
                            {{ item.label }}
                            <svg v-if="activeSection === item.id" xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </button>
                        <div class="my-2 border-t border-border/40"></div>
                        <Link v-if="page.props.auth.user" :href="route('dashboard')"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                        Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium hover:text-primary">Log in</Link>
                            <Link :href="route('register')">
                            <Button variant="default" size="sm">Sign up free</Button>
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <main class="flex-1 pt-24">
            <!-- Hero Section -->
            <HeroSection />


            <!-- Features Section -->
            <FeaturesSection />

            <!-- How It Works Section -->
            <HowItWorksSection />

            <!-- Social Proof Section -->
            <SocialProofSection />

            <!-- Demo Section -->
            <DemoSection />

            <!-- Pricing Section -->
            <PricingSection />

            <!-- Talk To Us Section -->
            <TalkToUsSection />
        </main>

        <!-- Footer -->
        <FooterSection />

        <!-- Scroll to Top Button -->
        <ScrollToTop :show-after="400" position="bottom-right" size="md" variant="default" :smooth="true"
            :duration="1200" @scroll-start="onScrollStart" @scroll-complete="onScrollComplete" />

        <!-- Chat Widget -->
        <ChatWidget position="bottom-left" bot-name="RillTech Assistant" bot-avatar="/images/ai2.jpg"
            welcome-message="Hi! I'm your AI assistant. I can help you learn about our AI agents, pricing, features, and answer any questions you have. How can I help you today?"
            placeholder="Ask me anything about RillTech..." :max-messages="50" :auto-open="false"
            :show-typing-indicator="true" @message-sent="onChatMessageSent" @chat-opened="onChatOpened"
            @chat-closed="onChatClosed" />
    </div>
</template>

<style>
/* Global animations for the landing page */
.landing-page {
  overflow-x: hidden; /* Prevent horizontal scrolling from animations */
}

/* Smooth scrolling for the entire page */
html.landing-page-active {
  scroll-behavior: smooth;
}

/* Add a subtle parallax effect to all sections */
.animate-section {
  /* Removed will-change to reduce memory consumption */
}

/* Enhance transitions between sections */
.floating-navbar {
  /* Removed will-change to reduce memory consumption */
  transition: padding 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
}

/* Improve button hover animations */
.btn {
  transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Primary button glow effect */
.btn.bg-primary {
  box-shadow: 0 4px 14px rgba(var(--primary), 0.3);
}

.btn.bg-primary:hover {
  box-shadow: 0 6px 20px rgba(var(--primary), 0.4);
}

/* Button click effect */
.btn:active {
  transform: translateY(0);
  transition: transform 0.1s ease;
}

/* Add subtle hover effects to cards */
.card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
</style>
