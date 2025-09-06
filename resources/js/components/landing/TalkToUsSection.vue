<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { toast } from 'vue-sonner';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

// Contact Form State
const contactForm = ref({
  name: '',
  email: '',
  phone: '',
  company: '',
  subject: '',
  message: ''
});
const isContactSubmitting = ref(false);
const contactFormSubmitted = ref(false);

// Newsletter Form State
const newsletterForm = ref({
  email: '',
  name: ''
});
const isNewsletterSubmitting = ref(false);
const newsletterFormSubmitted = ref(false);

// Contact Form Submission
const submitContactForm = async () => {
  if (!contactForm.value.name || !contactForm.value.email || !contactForm.value.subject || !contactForm.value.message) {
    toast('Please fill in all required fields');
    return;
  }

  isContactSubmitting.value = true;

  try {
    const response = await axios.post('/contact', {
      ...contactForm.value,
      source: 'landing_page'
    });

    if (response.data.success) {
      contactFormSubmitted.value = true;

      // Success animation
      gsap.from('.contact-success-animation', {
        scale: 0,
        rotation: 180,
        duration: 0.6,
        ease: 'back.out(1.7)'
      });

      // Confetti animation
      createConfetti('.contact-success-animation');

      toast(response.data.message);

      // Reset form
      contactForm.value = {
        name: '',
        email: '',
        phone: '',
        company: '',
        subject: '',
        message: ''
      };

      // Reset form state after showing success message
      setTimeout(() => {
        contactFormSubmitted.value = false;
      }, 5000);
    }
  } catch (error: any) {
    console.error('Contact form error:', error);
    const errorMessage = error.response?.data?.message || 'Sorry, there was an error sending your message. Please try again.';
    toast(errorMessage);
  } finally {
    isContactSubmitting.value = false;
  }
};

// Newsletter Form Submission
const submitNewsletterForm = async () => {
  if (!newsletterForm.value.email) {
    toast('Please enter your email address');
    return;
  }

  isNewsletterSubmitting.value = true;

  try {
    const response = await axios.post('/newsletter/subscribe', {
      ...newsletterForm.value,
      source: 'landing_page'
    });

    if (response.data.success) {
      newsletterFormSubmitted.value = true;

      // Success animation with floating particles
      gsap.from('.newsletter-success-animation', {
        scale: 0,
        rotation: 360,
        duration: 0.8,
        ease: 'back.out(1.7)'
      });

      // Create floating email icons
      createFloatingIcons('.newsletter-success-animation');

      toast(response.data.message);

      // Reset form
      newsletterForm.value = {
        email: '',
        name: ''
      };

      // Reset form state after showing success message
      setTimeout(() => {
        newsletterFormSubmitted.value = false;
      }, 4000);
    }
  } catch (error: any) {
    console.error('Newsletter subscription error:', error);
    const errorMessage = error.response?.data?.message || 'Sorry, there was an error processing your subscription. Please try again.';
    toast(errorMessage);
  } finally {
    isNewsletterSubmitting.value = false;
  }
};

// Animation Functions
const createConfetti = (target: string) => {
  const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
  const container = document.querySelector(target);
  if (!container) return;

  for (let i = 0; i < 20; i++) {
    const confetti = document.createElement('div');
    confetti.style.cssText = `
      position: absolute;
      width: 8px;
      height: 8px;
      background: ${colors[Math.floor(Math.random() * colors.length)]};
      border-radius: 50%;
      pointer-events: none;
      z-index: 1000;
    `;

    container.appendChild(confetti);

    gsap.set(confetti, {
      x: 0,
      y: 0,
    });

    gsap.to(confetti, {
      x: (Math.random() - 0.5) * 200,
      y: (Math.random() - 0.5) * 200,
      rotation: Math.random() * 360,
      opacity: 0,
      duration: 1.5,
      ease: 'power2.out',
      onComplete: () => confetti.remove()
    });
  }
};

const createFloatingIcons = (target: string) => {
  const container = document.querySelector(target);
  if (!container) return;

  for (let i = 0; i < 8; i++) {
    const icon = document.createElement('div');
    icon.innerHTML = '📧';
    icon.style.cssText = `
      position: absolute;
      font-size: 20px;
      pointer-events: none;
      z-index: 1000;
    `;

    container.appendChild(icon);

    gsap.set(icon, {
      x: 0,
      y: 0,
    });

    gsap.to(icon, {
      x: (Math.random() - 0.5) * 150,
      y: -50 - Math.random() * 100,
      rotation: Math.random() * 360,
      opacity: 0,
      duration: 2,
      ease: 'power2.out',
      delay: i * 0.1,
      onComplete: () => icon.remove()
    });
  }
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
  <!-- Contact Form Section -->
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
          <div v-if="contactFormSubmitted" class="text-center py-8 relative">
            <div class="contact-success-animation relative inline-block">
              <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-green-500">
                <circle cx="12" cy="12" r="10"/>
                <path d="m8 12 3 3 6-6"/>
              </svg>
            </div>
            <h3 class="text-xl font-medium mb-2">Message Sent Successfully! 🎉</h3>
            <p class="text-muted-foreground">Thank you for reaching out. Our team will get back to you within 24 hours.</p>
          </div>

          <form v-else @submit.prevent="submitContactForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="contact-name" class="block text-sm font-medium mb-2">Name *</label>
                <Input id="contact-name" v-model="contactForm.name" placeholder="Your full name" required />
              </div>

              <div>
                <label for="contact-email" class="block text-sm font-medium mb-2">Email *</label>
                <Input id="contact-email" v-model="contactForm.email" type="email" placeholder="your.email@example.com" required />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="contact-phone" class="block text-sm font-medium mb-2">Phone</label>
                <Input id="contact-phone" v-model="contactForm.phone" type="tel" placeholder="+1 (555) 123-4567" />
              </div>

              <div>
                <label for="contact-company" class="block text-sm font-medium mb-2">Company</label>
                <Input id="contact-company" v-model="contactForm.company" placeholder="Your company name" />
              </div>
            </div>

            <div>
              <label for="contact-subject" class="block text-sm font-medium mb-2">Subject *</label>
              <Input id="contact-subject" v-model="contactForm.subject" placeholder="What can we help you with?" required />
            </div>

            <div>
              <label for="contact-message" class="block text-sm font-medium mb-2">Message *</label>
              <Textarea id="contact-message" v-model="contactForm.message" placeholder="Tell us about your needs, requirements, or any questions you have..." rows="5" required />
            </div>

            <Button type="submit" class="w-full" :disabled="isContactSubmitting">
              <span v-if="isContactSubmitting" class="mr-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              {{ isContactSubmitting ? 'Sending Message...' : 'Send Message' }}
            </Button>
          </form>
        </Card>
      </div>
    </div>
  </section>

  <!-- Newsletter Section -->
  <section id="newsletter" class="animate-section relative py-20 md:py-32 bg-gradient-to-br from-primary/5 via-background to-secondary/5">
    <div class="container mx-auto px-4 md:px-6">
      <div class="mx-auto max-w-4xl">
        <div class="text-center animate-in mb-12">
          <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
            Stay Updated with AI Innovations
          </h2>
          <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
            Get the latest insights on AI agents, industry trends, and product updates delivered straight to your inbox. Join thousands of AI enthusiasts and professionals.
          </p>
        </div>

        <div class="mx-auto max-w-2xl animate-in">
          <Card class="p-6 md:p-8 bg-background/80 backdrop-blur-sm border-2 border-primary/10">
            <div v-if="newsletterFormSubmitted" class="text-center py-8 relative">
              <div class="newsletter-success-animation relative inline-block">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-primary to-secondary rounded-full flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                  </svg>
                </div>
              </div>
              <h3 class="text-xl font-medium mb-2">Welcome to Our Newsletter! 📧</h3>
              <p class="text-muted-foreground">You're all set! Check your email for a welcome message and get ready for amazing AI content.</p>
            </div>

            <form v-else @submit.prevent="submitNewsletterForm" class="space-y-6">
              <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 rounded-full text-sm font-medium text-primary mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                  </svg>
                  Weekly AI Insights
                </div>
                <h3 class="text-lg font-semibold mb-2">Join 10,000+ AI Enthusiasts</h3>
                <p class="text-sm text-muted-foreground">No spam, unsubscribe anytime</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="newsletter-name" class="block text-sm font-medium mb-2">Name (Optional)</label>
                  <Input id="newsletter-name" v-model="newsletterForm.name" placeholder="Your name" />
                </div>

                <div>
                  <label for="newsletter-email" class="block text-sm font-medium mb-2">Email Address *</label>
                  <Input id="newsletter-email" v-model="newsletterForm.email" type="email" placeholder="your.email@example.com" required />
                </div>
              </div>

              <Button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary hover:from-primary/90 hover:to-secondary/90" :disabled="isNewsletterSubmitting">
                <span v-if="isNewsletterSubmitting" class="mr-2">
                  <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </span>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
                {{ isNewsletterSubmitting ? 'Subscribing...' : 'Subscribe to Newsletter' }}
              </Button>

              <div class="flex items-center justify-center gap-6 text-xs text-muted-foreground">
                <div class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                  </svg>
                  Weekly Updates
                </div>
                <div class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                  </svg>
                  No Spam
                </div>
                <div class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/>
                  </svg>
                  Easy Unsubscribe
                </div>
              </div>
            </form>
          </Card>
        </div>
      </div>
    </div>
  </section>
</template>
