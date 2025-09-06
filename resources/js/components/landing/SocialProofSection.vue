<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

onMounted(() => {
  // Animate the logos marquee
  initMarqueeAnimation();

  // Animate testimonials
  gsap.utils.toArray('.testimonial-card').forEach((card: any, i) => {
    gsap.from(card, {
      y: 30,
      opacity: 0,
      duration: 0.7,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: card,
        start: 'top 85%',
        toggleActions: 'play none none none'
      },
      delay: i * 0.2 // Stagger the animations
    });
  });
});

const initMarqueeAnimation = () => {
  // Create infinite scrolling effect for logos
  gsap.to('.logos-container', {
    x: '-50%',
    ease: 'none',
    duration: 20,
    repeat: -1
  });
};

// Sample company logos
const companies = [
  { name: 'Rill Tech', logo: '/images/logo.png' },
  { name: 'Osvia Tech', logo: 'images/osviatech.png' },
  { name: 'Laravel', logo: 'images/favicon.ico' },

];

// Sample testimonials
const testimonials = [
  {
    quote: "This platform has completely transformed how we handle customer support. Our AI assistant handles 80% of inquiries automatically.",
    author: "Sarah Johnson",
    role: "Customer Success Manager",
    company: "TechCorp",
    avatar: "SJ"
  },
  {
    quote: "We built our AI assistant in just two days. What would have taken months of development was done in hours.",
    author: "Michael Chen",
    role: "Product Lead",
    company: "InnovateLabs",
    avatar: "MC"
  },
  {
    quote: "The drag-and-drop interface made it incredibly easy to create a sophisticated AI agent without any coding knowledge.",
    author: "Emily Rodriguez",
    role: "Marketing Director",
    company: "GrowthBrand",
    avatar: "ER"
  }
];
</script>

<template>
    <section id="social-proof" class="animate-section relative py-20 md:py-32">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Trusted By</h2>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    Join hundreds of companies already building powerful AI assistants with our platform.
                </p>
            </div>

            <!-- Company logos marquee -->
            <div class="relative mb-20 overflow-hidden">
                <div class="logos-container flex w-fit items-center gap-12 py-4">
                    <!-- First set of logos -->
                    <div v-for="company in companies" :key="company.name" class="">
                        <Avatar class="h-15 w-15 overflow-hidden rounded-full">
                            <AvatarImage v-if="company" :src="company.logo" :alt="company.logo" />
                            <AvatarFallback class="rounded-lg text-black dark:text-white">
                                {{ company.logo }}
                            </AvatarFallback>
                        </Avatar>
                        <p class="font-bold">{{ company.name }}</p>
                    </div>

                    <!-- Duplicate set for continuous scrolling -->
                    <div v-for="company in companies" :key="`dup-${company.name}`">
                        <Avatar class="h-15 w-15 overflow-hidden rounded-full">
                            <AvatarImage v-if="company" :src="company.logo" :alt="company.logo" />
                            <AvatarFallback class="rounded-lg text-black dark:text-white">
                                {{ company.logo }}
                            </AvatarFallback>
                        </Avatar>
                        <p class="font-bold">{{ company.name }}</p>
                    </div>

                    <div v-for="company in companies" :key="`dup-${company.name}`">
                        <Avatar class="h-15 w-15 overflow-hidden rounded-full">
                            <AvatarImage v-if="company" :src="company.logo" :alt="company.logo" />
                            <AvatarFallback class="rounded-lg text-black dark:text-white">
                                {{ company.logo }}
                            </AvatarFallback>
                        </Avatar>
                        <p class="font-bold">{{ company.name }}</p>
                    </div>

                    <div v-for="company in companies" :key="`dup-${company.name}`">
                        <Avatar class="h-15 w-15 overflow-hidden rounded-full">
                            <AvatarImage v-if="company" :src="company.logo" :alt="company.logo" />
                            <AvatarFallback class="rounded-lg text-black dark:text-white">
                                {{ company.logo }}
                            </AvatarFallback>
                        </Avatar>
                        <p class="font-bold">{{ company.name }}</p>
                    </div>
                </div>

                <!-- Gradient overlays for smooth fade effect -->
                <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-background to-transparent"></div>
                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-background to-transparent"></div>
            </div>

            <!-- Testimonials -->
            <div class="grid gap-6 md:grid-cols-3">
                <div v-for="(testimonial, index) in testimonials" :key="index" class="testimonial-card">
                    <Card
                        class="h-full border-border/50 transition-all duration-300 hover:border-primary/50 hover:shadow-lg">
                        <CardContent class="p-6">
                            <div class="mb-4 text-4xl text-muted">"</div>
                            <p class="mb-6 text-lg">{{ testimonial.quote }}</p>

                            <div class="flex items-center gap-4">
                                <Avatar>
                                    <AvatarFallback class="bg-primary/10 text-primary">{{ testimonial.avatar }}
                                    </AvatarFallback>
                                </Avatar>

                                <div>
                                    <h4 class="font-semibold">{{ testimonial.author }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ testimonial.role }}, {{
                                        testimonial.company }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </section>
</template>
