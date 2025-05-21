<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

const activeTab = ref('build');

onMounted(() => {
  // Animate the demo section
  gsap.from('.demo-content', {
    y: 50,
    opacity: 0,
    duration: 0.8,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '#demo',
      start: 'top 70%',
      toggleActions: 'play none none none'
    }
  });
  
  // Animate the demo interface
  gsap.from('.demo-interface', {
    y: 50,
    opacity: 0,
    duration: 0.8,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '#demo',
      start: 'top 70%',
      toggleActions: 'play none none none'
    },
    delay: 0.3
  });
  
  // Setup chat animation
  setupChatAnimation();
});

const setupChatAnimation = () => {
  // Animate the chat messages appearing one by one
  const messages = document.querySelectorAll('.chat-message');
  
  messages.forEach((message, index) => {
    gsap.from(message, {
      y: 20,
      opacity: 0,
      duration: 0.5,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '.chat-container',
        start: 'top 70%',
        toggleActions: 'play none none none'
      },
      delay: 0.5 + (index * 0.7) // Stagger the animations
    });
  });
};

// Demo chat messages
const chatMessages = [
  {
    sender: 'user',
    message: 'Can you help me find information about our Q2 sales performance?'
  },
  {
    sender: 'ai',
    message: 'I found the Q2 sales report. Your team exceeded targets by 12%, with $1.2M in revenue. The top-performing product was the Enterprise plan. Would you like to see the detailed breakdown?'
  },
  {
    sender: 'user',
    message: 'Yes, please show me the breakdown by region.'
  },
  {
    sender: 'ai',
    message: 'Here\'s the regional breakdown for Q2:\n- North America: $580K (↑15%)\n- Europe: $420K (↑8%)\n- Asia-Pacific: $200K (↑22%)\n\nAPAC showed the strongest growth. Would you like me to prepare a presentation with these insights?'
  }
];
</script>

<template>
  <section id="demo" class="animate-section relative bg-muted/30 py-20 md:py-32">
    <div class="container mx-auto px-4 md:px-6">
      <div class="grid items-center gap-12 lg:grid-cols-2">
        <!-- Demo content -->
        <div class="demo-content">
          <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">See It In Action</h2>
          <p class="mb-6 text-lg text-muted-foreground">
            Watch how easy it is to build, train, and deploy your own AI assistant. No coding required.
          </p>
          
          <Tabs v-model="activeTab" class="w-full">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="build">Build</TabsTrigger>
              <TabsTrigger value="train">Train</TabsTrigger>
              <TabsTrigger value="deploy">Deploy</TabsTrigger>
            </TabsList>
            <TabsContent value="build" class="space-y-4 pt-4">
              <h3 class="text-xl font-semibold">Drag & Drop Interface</h3>
              <p class="text-muted-foreground">
                Our intuitive interface lets you build your AI assistant by simply dragging and dropping components. Choose from pre-built templates or start from scratch.
              </p>
              <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                <li>Select from dozens of pre-built templates</li>
                <li>Customize the UI to match your brand</li>
                <li>Add custom fields and data sources</li>
              </ul>
            </TabsContent>
            <TabsContent value="train" class="space-y-4 pt-4">
              <h3 class="text-xl font-semibold">Train with Your Data</h3>
              <p class="text-muted-foreground">
                Upload your documents, connect your data sources, and train your AI assistant to understand your specific domain and terminology.
              </p>
              <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                <li>Upload PDFs, docs, spreadsheets, and more</li>
                <li>Connect to your existing databases</li>
                <li>Test and refine responses in real-time</li>
              </ul>
            </TabsContent>
            <TabsContent value="deploy" class="space-y-4 pt-4">
              <h3 class="text-xl font-semibold">One-Click Deployment</h3>
              <p class="text-muted-foreground">
                Deploy your AI assistant to your website, app, or internal tools with a single click. No technical setup required.
              </p>
              <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                <li>Embed with a simple code snippet</li>
                <li>Integrate via our comprehensive API</li>
                <li>Deploy to multiple channels simultaneously</li>
              </ul>
            </TabsContent>
          </Tabs>
          
          <div class="mt-8">
            <Button size="lg" class="gap-2">
              Watch Full Demo
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </Button>
          </div>
        </div>
        
        <!-- Demo interface -->
        <div class="demo-interface">
          <div class="overflow-hidden rounded-lg border border-border/50 bg-card/50 p-2 shadow-xl backdrop-blur-sm">
            <div class="rounded-md bg-card">
              <!-- Chat interface mockup -->
              <div class="border-b border-border/50 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full bg-primary/20"></div>
                    <div class="font-medium">Sales Assistant</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                    </Button>
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </Button>
                  </div>
                </div>
              </div>
              
              <!-- Chat messages -->
              <div class="chat-container h-[300px] overflow-y-auto p-4">
                <div v-for="(message, index) in chatMessages" :key="index" class="chat-message mb-4">
                  <div :class="[
                    'flex gap-3',
                    message.sender === 'user' ? 'justify-end' : 'justify-start'
                  ]">
                    <div v-if="message.sender === 'ai'" class="h-8 w-8 flex-shrink-0 rounded-full bg-primary/20"></div>
                    <div :class="[
                      'max-w-[80%] rounded-lg p-3',
                      message.sender === 'user' 
                        ? 'bg-primary text-primary-foreground' 
                        : 'bg-muted'
                    ]">
                      <p class="whitespace-pre-line">{{ message.message }}</p>
                    </div>
                    <div v-if="message.sender === 'user'" class="h-8 w-8 flex-shrink-0 rounded-full bg-muted"></div>
                  </div>
                </div>
              </div>
              
              <!-- Chat input -->
              <div class="border-t border-border/50 p-4">
                <div class="flex items-center gap-2">
                  <div class="relative flex-1">
                    <input type="text" placeholder="Type your message..." class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                    <div class="absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-1">
                      <Button variant="ghost" size="icon" class="h-7 w-7">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-paperclip"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                      </Button>
                      <Button variant="ghost" size="icon" class="h-7 w-7">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mic"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>
                      </Button>
                    </div>
                  </div>
                  <Button size="icon" class="h-9 w-9">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
