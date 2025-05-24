<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';

interface Message {
  id: string;
  content: string;
  sender: 'user' | 'bot';
  timestamp: Date;
  typing?: boolean;
}

interface Props {
  position?: 'bottom-right' | 'bottom-left';
  botName?: string;
  botAvatar?: string;
  welcomeMessage?: string;
  placeholder?: string;
  primaryColor?: string;
  maxMessages?: number;
  autoOpen?: boolean;
  showTypingIndicator?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  position: 'bottom-right',
  botName: 'RillTech Assistant',
  botAvatar: '/images/ai2.jpg',
  welcomeMessage: 'Hi! I\'m your AI assistant. How can I help you today?',
  placeholder: 'Type your message...',
  primaryColor: 'hsl(var(--primary))',
  maxMessages: 100,
  autoOpen: false,
  showTypingIndicator: true
});

const emit = defineEmits<{
  (e: 'message-sent', message: string): void;
  (e: 'chat-opened'): void;
  (e: 'chat-closed'): void;
}>();

// State
const isOpen = ref(props.autoOpen);
const messages = ref<Message[]>([]);
const currentMessage = ref('');
const isTyping = ref(false);
const unreadCount = ref(0);
const messagesContainer = ref<HTMLElement>();
const scrollContainer = ref<HTMLElement>();

// Computed
const positionClasses = computed(() => ({
  'bottom-right': 'bottom-4 right-4',
  'bottom-left': 'bottom-4 left-4'
}));

const hasUnreadMessages = computed(() => unreadCount.value > 0);

// Initialize with welcome message
onMounted(() => {
  addBotMessage(props.welcomeMessage);
});

// Message management
const addMessage = (content: string, sender: 'user' | 'bot') => {
  const message: Message = {
    id: Date.now().toString(),
    content,
    sender,
    timestamp: new Date()
  };

  messages.value.push(message);

  // Limit messages
  if (messages.value.length > props.maxMessages) {
    messages.value = messages.value.slice(-props.maxMessages);
  }

  // Update unread count if chat is closed
  if (!isOpen.value && sender === 'bot') {
    unreadCount.value++;
  }

  // Auto scroll to bottom with multiple attempts
  nextTick(() => {
    scrollToBottom();
    // Additional scroll attempt after a short delay for bot messages
    setTimeout(() => {
      scrollToBottom();
    }, 100);
  });
};

const addBotMessage = (content: string) => {
  addMessage(content, 'bot');
};

const addUserMessage = (content: string) => {
  addMessage(content, 'user');
};

// Chat actions
const toggleChat = () => {
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    unreadCount.value = 0;
    emit('chat-opened');
    nextTick(() => {
      // Multiple scroll attempts to ensure it works
      scrollToBottom();
      setTimeout(() => {
        scrollToBottom();
        focusInput();
      }, 100);
      setTimeout(() => {
        scrollToBottom();
      }, 300);
    });
  } else {
    emit('chat-closed');
  }
};

const sendMessage = async () => {
  const message = currentMessage.value.trim();
  if (!message) return;

  // Add user message
  addUserMessage(message);
  currentMessage.value = '';

  // Emit event
  emit('message-sent', message);

  // Show typing indicator
  if (props.showTypingIndicator) {
    isTyping.value = true;

    // Simulate bot response delay
    setTimeout(() => {
      isTyping.value = false;
      // Add bot response (this would typically come from your backend)
      addBotMessage(generateBotResponse(message));

      // Ensure scroll to bottom after bot response
      setTimeout(() => {
        scrollToBottom();
      }, 50);
    }, 1000 + Math.random() * 2000);
  }
};

const handleKeyPress = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (scrollContainer.value) {
      // Smooth scroll to bottom
      scrollContainer.value.scrollTo({
        top: scrollContainer.value.scrollHeight,
        behavior: 'smooth'
      });
    }
  });
};

const focusInput = () => {
  const input = document.querySelector('.chat-input') as HTMLInputElement;
  if (input) {
    input.focus();
  }
};

// Simple bot response generator (replace with your AI logic)
const generateBotResponse = (userMessage: string): string => {
  const responses = [
    "That's interesting! Tell me more about that.",
    "I understand. How can I help you with that?",
    "Thanks for sharing! Is there anything specific you'd like to know?",
    "I'm here to help! What would you like to explore?",
    "Great question! Let me think about that for a moment.",
    "I'd be happy to assist you with that. Can you provide more details?",
    "That sounds important. How can I support you with this?",
    "I see what you mean. What's your main goal here?"
  ];

  return responses[Math.floor(Math.random() * responses.length)];
};

// Format timestamp
const formatTime = (date: Date) => {
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Debug function to test auto-scroll
const testAutoScroll = () => {
  console.log('Testing auto-scroll...');
  addBotMessage('This is a test message to verify auto-scroll is working properly!');
};

// Expose for debugging (remove in production)
if (typeof window !== 'undefined') {
  (window as any).testChatScroll = testAutoScroll;
}

// Cleanup
onUnmounted(() => {
  // Clean up any intervals or listeners
});
</script>

<template>
  <div :class="['fixed z-50', positionClasses[position]]">
    <!-- Chat Window -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      leave-active-class="transition-all duration-200 ease-in"
      enter-from-class="opacity-0 scale-95 translate-y-4"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-4"
    >
      <div
        v-if="isOpen"
        class="mb-4 w-80 sm:w-96 h-[500px] bg-background border border-border rounded-lg shadow-2xl backdrop-blur-sm overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-border bg-muted/50">
          <div class="flex items-center gap-3">
            <Avatar class="h-8 w-8">
              <AvatarImage :src="botAvatar" :alt="botName" />
              <AvatarFallback>AI</AvatarFallback>
            </Avatar>
            <div>
              <h3 class="font-semibold text-sm">{{ botName }}</h3>
              <p class="text-xs text-muted-foreground">
                {{ isTyping ? 'Typing...' : 'Online' }}
              </p>
            </div>
          </div>
          <Button
            @click="toggleChat"
            variant="ghost"
            size="sm"
            class="h-8 w-8 p-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6 6 18"/>
              <path d="m6 6 12 12"/>
            </svg>
          </Button>
        </div>

        <!-- Messages -->
        <div ref="scrollContainer" class="flex-1 h-[360px] overflow-y-auto scroll-smooth">
          <div ref="messagesContainer" class="p-4 space-y-4 chat-messages-container">
            <div
              v-for="message in messages"
              :key="message.id"
              :class="[
                'flex gap-3',
                message.sender === 'user' ? 'justify-end' : 'justify-start'
              ]"
            >
              <!-- Bot Avatar -->
              <Avatar v-if="message.sender === 'bot'" class="h-6 w-6 mt-1">
                <AvatarImage :src="botAvatar" :alt="botName" />
                <AvatarFallback class="text-xs">AI</AvatarFallback>
              </Avatar>

              <!-- Message Bubble -->
              <div
                :class="[
                  'max-w-[80%] rounded-lg px-3 py-2 text-sm',
                  message.sender === 'user'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-foreground'
                ]"
              >
                <p>{{ message.content }}</p>
                <p
                  :class="[
                    'text-xs mt-1 opacity-70',
                    message.sender === 'user' ? 'text-right' : 'text-left'
                  ]"
                >
                  {{ formatTime(message.timestamp) }}
                </p>
              </div>
            </div>

            <!-- Typing Indicator -->
            <div v-if="isTyping" class="flex gap-3 justify-start">
              <Avatar class="h-6 w-6 mt-1">
                <AvatarImage :src="botAvatar" :alt="botName" />
                <AvatarFallback class="text-xs">AI</AvatarFallback>
              </Avatar>
              <div class="bg-muted rounded-lg px-3 py-2">
                <div class="flex gap-1">
                  <div class="w-2 h-2 bg-muted-foreground rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                  <div class="w-2 h-2 bg-muted-foreground rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                  <div class="w-2 h-2 bg-muted-foreground rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-border">
          <div class="flex gap-2">
            <Input
              v-model="currentMessage"
              :placeholder="placeholder"
              class="chat-input flex-1"
              @keypress="handleKeyPress"
            />
            <Button
              @click="sendMessage"
              :disabled="!currentMessage.trim()"
              size="sm"
              class="px-3"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m22 2-7 20-4-9-9-4Z"/>
                <path d="M22 2 11 13"/>
              </svg>
            </Button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Chat Button -->
    <Button
      @click="toggleChat"
      :class="[
        'relative h-14 w-14 rounded-full shadow-lg hover:scale-110 transition-all duration-200',
        isOpen ? 'bg-muted text-muted-foreground' : 'bg-primary text-primary-foreground'
      ]"
    >
      <!-- Unread Badge -->
      <Badge
        v-if="hasUnreadMessages && !isOpen"
        class="absolute -top-2 -right-2 h-6 w-6 rounded-full p-0 flex items-center justify-center text-xs"
        variant="destructive"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </Badge>

      <!-- Icon -->
      <Transition
        enter-active-class="transition-all duration-200"
        leave-active-class="transition-all duration-200"
        enter-from-class="opacity-0 rotate-180 scale-75"
        enter-to-class="opacity-100 rotate-0 scale-100"
        leave-from-class="opacity-100 rotate-0 scale-100"
        leave-to-class="opacity-0 rotate-180 scale-75"
        mode="out-in"
      >
        <svg
          v-if="!isOpen"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          key="chat"
        >
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          key="close"
        >
          <path d="M18 6 6 18"/>
          <path d="m6 6 12 12"/>
        </svg>
      </Transition>
    </Button>
  </div>
</template>

<style scoped>
/* Custom scrollbar for messages */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground)) transparent;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground));
  border-radius: 2px;
}

/* Typing animation */
@keyframes bounce {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-10px);
  }
}

/* Chat button pulse effect */
.chat-button-pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(var(--primary), 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(var(--primary), 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(var(--primary), 0);
  }
}
</style>
