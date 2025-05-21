<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

const name = ref('');
const email = ref('');
const message = ref('');
const isSubmitting = ref(false);
const formSubmitted = ref(false);

const submitForm = async () => {
  if (!name.value || !email.value || !message.value) {
    return;
  }
  
  isSubmitting.value = true;
  
  // Simulate form submission
  setTimeout(() => {
    formSubmitted.value = true;
    isSubmitting.value = false;
    
    // Reset form after submission
    name.value = '';
    email.value = '';
    message.value = '';
    
    // Reset form state after showing success message
    setTimeout(() => {
      formSubmitted.value = false;
    }, 5000);
  }, 1000);
};

onMounted(() => {
  // Animate the section
  gsap.from('#talk-to-us .animate-in', {
    y: 40,
    opacity: 0,
    duration: 0.8,
    ease: 'power2.out',
    stagger: 0.2,
    scrollTrigger: {
      trigger: '#talk-to-us',
      start: 'top 80%',
      toggleActions: 'play none none none'
    }
  });
});
</script>

<template>
  <section id="talk-to-us" class="animate-section relative py-20 md:py-32 bg-muted/30">
    <div class="container mx-auto px-4 md:px-6">
      <div class="mx-auto max-w-3xl text-center animate-in">
        <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">Talk to Our Team</h2>
        <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
          Have questions about our enterprise solutions or need a custom plan? Our team is ready to help you find the perfect solution for your business.
        </p>
      </div>
      
      <div class="mt-12 mx-auto max-w-2xl animate-in">
        <Card class="p-6 md:p-8">
          <div v-if="formSubmitted" class="text-center py-8">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-green-500"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 6-6"/></svg>
            <h3 class="text-xl font-medium mb-2">Message Sent!</h3>
            <p class="text-muted-foreground">Thank you for reaching out. Our team will get back to you shortly.</p>
          </div>
          
          <form v-else @submit.prevent="submitForm" class="space-y-6">
            <div class="space-y-4">
              <div>
                <label for="name" class="block text-sm font-medium mb-2">Name</label>
                <Input id="name" v-model="name" placeholder="Your name" required />
              </div>
              
              <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <Input id="email" v-model="email" type="email" placeholder="your.email@example.com" required />
              </div>
              
              <div>
                <label for="message" class="block text-sm font-medium mb-2">Message</label>
                <Textarea id="message" v-model="message" placeholder="Tell us about your needs and requirements" rows="5" required />
              </div>
            </div>
            
            <Button type="submit" class="w-full" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="mr-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              {{ isSubmitting ? 'Sending...' : 'Send Message' }}
            </Button>
          </form>
        </Card>
      </div>
    </div>
  </section>
</template>
