<script setup lang="ts">
import { onMounted } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

onMounted(() => {
  // Animate steps when they come into view
  gsap.utils.toArray('.step-card').forEach((card: any, i) => {
    gsap.from(card, {
      y: 50,
      opacity: 0,
      duration: 0.8,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: card,
        start: 'top 80%',
        toggleActions: 'play none none none'
      },
      delay: i * 0.2 // Stagger the animations
    });
  });
  
  // Animate the connecting lines
  gsap.from('.connector', {
    scaleY: 0,
    transformOrigin: 'top',
    duration: 1.5,
    ease: 'power2.inOut',
    scrollTrigger: {
      trigger: '#how-it-works',
      start: 'top 60%',
      toggleActions: 'play none none none'
    }
  });
});

const steps = [
  {
    title: 'Choose a Template',
    description: 'Start with pre-built templates for customer service, data analysis, content creation, and more.',
    icon: 'template',
    color: 'bg-blue-500'
  },
  {
    title: 'Customize via Form & Drag-Drop',
    description: 'Easily customize your AI assistant with our intuitive drag-and-drop interface. No coding required.',
    icon: 'customize',
    color: 'bg-purple-500'
  },
  {
    title: 'Train & Test in Real-Time',
    description: 'Train your AI with your data and test its responses instantly. Refine until perfect.',
    icon: 'train',
    color: 'bg-green-500'
  },
  {
    title: 'Deploy Your Agent Anywhere',
    description: 'Deploy to your website, app, or internal tools with a single click. Integrate via API or embed code.',
    icon: 'deploy',
    color: 'bg-amber-500'
  }
];
</script>

<template>
  <section id="how-it-works" class="animate-section relative py-20 md:py-32">
    <div class="container mx-auto px-4 md:px-6">
      <div class="mb-16 text-center">
        <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">How It Works</h2>
        <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
          Building powerful AI assistants has never been easier. Follow these simple steps to create your own custom AI agent.
        </p>
      </div>
      
      <div class="relative mx-auto max-w-4xl">
        <!-- Steps -->
        <div class="relative z-10 space-y-12 md:space-y-0 md:grid md:grid-cols-2 md:gap-8 lg:gap-16">
          <div v-for="(step, index) in steps" :key="index" class="step-card relative">
            <Card class="overflow-hidden border-border/50 transition-all duration-300 hover:border-primary/50 hover:shadow-lg">
              <div class="absolute right-4 top-4 flex h-12 w-12 items-center justify-center rounded-full" :class="step.color + '/10'">
                <!-- Template Icon -->
                <svg v-if="step.icon === 'template'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-template" :class="step.color.replace('bg-', 'text-')"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                
                <!-- Customize Icon -->
                <svg v-if="step.icon === 'customize'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mouse-pointer-click" :class="step.color.replace('bg-', 'text-')"><path d="m9 9 5 12 1.8-5.2L21 14Z"/><path d="M7.2 2.2 8 5.1"/><path d="m5.1 8-2.9-.8"/><path d="M14 4.1 12 6"/><path d="m6 12-1.9 2"/></svg>
                
                <!-- Train Icon -->
                <svg v-if="step.icon === 'train'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain-circuit" :class="step.color.replace('bg-', 'text-')"><path d="M12 4.5a2.5 2.5 0 0 0-4.96-.46 2.5 2.5 0 0 0-1.98 3 2.5 2.5 0 0 0-1.32 4.24 3 3 0 0 0 .34 5.58 2.5 2.5 0 0 0 2.96 3.08 2.5 2.5 0 0 0 4.91.05L12 20V4.5Z"/><path d="M16 8V5c0-1.1.9-2 2-2"/><path d="M12 13h4"/><path d="M12 18h6a2 2 0 0 1 2 2v1"/><path d="M12 8h8"/><path d="M20.5 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/><path d="M16.5 13a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/><path d="M20.5 21a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/><path d="M18.5 3a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/></svg>
                
                <!-- Deploy Icon -->
                <svg v-if="step.icon === 'deploy'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket" :class="step.color.replace('bg-', 'text-')"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
              </div>
              
              <CardHeader>
                <CardTitle class="text-xl font-bold">
                  <span class="mr-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">{{ index + 1 }}</span>
                  {{ step.title }}
                </CardTitle>
              </CardHeader>
              
              <CardContent>
                <p class="text-muted-foreground">{{ step.description }}</p>
              </CardContent>
            </Card>
          </div>
        </div>
        
        <!-- Connecting lines (visible on md+ screens) -->
        <div class="connector absolute left-1/2 top-0 hidden h-full w-0.5 -translate-x-1/2 bg-gradient-to-b from-primary/30 via-primary/50 to-primary/30 md:block"></div>
        
        <!-- Connecting dots -->
        <div class="absolute left-1/2 top-[25%] hidden h-4 w-4 -translate-x-1/2 rounded-full bg-primary md:block"></div>
        <div class="absolute left-1/2 top-[50%] hidden h-4 w-4 -translate-x-1/2 rounded-full bg-primary md:block"></div>
        <div class="absolute left-1/2 top-[75%] hidden h-4 w-4 -translate-x-1/2 rounded-full bg-primary md:block"></div>
      </div>
    </div>
  </section>
</template>
