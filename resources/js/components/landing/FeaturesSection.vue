<script setup lang="ts">
import { onMounted } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

onMounted(() => {
  // Animate features when they come into view
  gsap.utils.toArray('.feature-card').forEach((card: any, i) => {
    gsap.from(card, {
      y: 40,
      opacity: 0,
      duration: 0.7,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: card,
        start: 'top 85%',
        toggleActions: 'play none none none'
      },
      delay: i * 0.1 // Stagger the animations
    });
  });
  
  // Animate the heading and description
  gsap.from('.features-heading', {
    y: 30,
    opacity: 0,
    duration: 0.8,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '#features',
      start: 'top 80%',
      toggleActions: 'play none none none'
    }
  });
});

const features = [
  {
    title: 'Multi-Modal Inputs',
    description: 'Process text, images, audio, and video inputs to create comprehensive AI experiences.',
    icon: 'input',
    color: 'bg-blue-500'
  },
  {
    title: 'Real-Time Analytics',
    description: 'Monitor your AI agent\'s performance with detailed analytics and insights.',
    icon: 'chart',
    color: 'bg-green-500'
  },
  {
    title: 'Team Collaboration',
    description: 'Work together with your team to build, test, and refine your AI assistants.',
    icon: 'users',
    color: 'bg-amber-500'
  },
  {
    title: 'API Access',
    description: 'Connect your AI agent to any system with our comprehensive API.',
    icon: 'code',
    color: 'bg-purple-500'
  },
  {
    title: 'Custom Knowledge Base',
    description: 'Train your AI on your specific data, documents, and knowledge base.',
    icon: 'database',
    color: 'bg-pink-500'
  },
  {
    title: 'Workflow Automation',
    description: 'Automate complex workflows by connecting your AI agent to your existing tools.',
    icon: 'workflow',
    color: 'bg-indigo-500'
  }
];
</script>

<template>
  <section id="features" class="animate-section relative bg-muted/30 py-20 md:py-32">
    <div class="container mx-auto px-4 md:px-6">
      <div class="features-heading mb-16 text-center">
        <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Powerful Features</h2>
        <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
          Our platform provides everything you need to create sophisticated AI assistants without writing a single line of code.
        </p>
      </div>
      
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="(feature, index) in features" :key="index" class="feature-card">
          <Card class="h-full border-border/50 transition-all duration-300 hover:-translate-y-1 hover:border-primary/50 hover:shadow-lg">
            <CardHeader>
              <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg" :class="feature.color + '/10'">
                <!-- Input Icon -->
                <svg v-if="feature.icon === 'input'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-input" :class="feature.color.replace('bg-', 'text-')"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg>
                
                <!-- Chart Icon -->
                <svg v-if="feature.icon === 'chart'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart" :class="feature.color.replace('bg-', 'text-')"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                
                <!-- Users Icon -->
                <svg v-if="feature.icon === 'users'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users" :class="feature.color.replace('bg-', 'text-')"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                
                <!-- Code Icon -->
                <svg v-if="feature.icon === 'code'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-code" :class="feature.color.replace('bg-', 'text-')"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                
                <!-- Database Icon -->
                <svg v-if="feature.icon === 'database'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database" :class="feature.color.replace('bg-', 'text-')"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                
                <!-- Workflow Icon -->
                <svg v-if="feature.icon === 'workflow'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-git-branch" :class="feature.color.replace('bg-', 'text-')"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
              </div>
              
              <CardTitle>{{ feature.title }}</CardTitle>
            </CardHeader>
            
            <CardContent>
              <p class="text-muted-foreground">{{ feature.description }}</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </section>
</template>
