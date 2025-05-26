<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import RichTextMessage from './RichTextMessage.vue';

interface Message {
  id: string;
  content: string;
  sender: 'user' | 'bot';
  timestamp: Date;
  typing?: boolean;
  avatar?: string;
  isFallback?: boolean;
  fallbackReason?: string;
  aiProvider?: string;
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
const streamingMessageId = ref<string | null>(null);

// Generate a persistent session ID for this chat session
const sessionId = ref(`widget_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`);

// Reset chat function
const resetChat = () => {
  messages.value = [];
  streamingMessageId.value = null;
  // Generate new session ID to clear server-side history
  sessionId.value = `widget_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
};

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

const addBotMessage = (content: string, fallbackInfo?: { isFallback?: boolean; fallbackReason?: string; aiProvider?: string }) => {
  const message: Message = {
    id: Date.now().toString(),
    content,
    sender: 'bot',
    timestamp: new Date(),
    isFallback: fallbackInfo?.isFallback || (window as any).lastResponseFallbackInfo?.isFallback || false,
    fallbackReason: fallbackInfo?.fallbackReason || (window as any).lastResponseFallbackInfo?.fallbackReason,
    aiProvider: fallbackInfo?.aiProvider || (window as any).lastResponseFallbackInfo?.aiProvider || 'mistral'
  };

  messages.value.push(message);

  // Clear the stored fallback info
  (window as any).lastResponseFallbackInfo = null;

  // Limit messages
  if (messages.value.length > props.maxMessages) {
    messages.value = messages.value.slice(-props.maxMessages);
  }

  // Update unread count if chat is closed
  if (!isOpen.value) {
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

  // Show typing indicator and get streaming AI response
  if (props.showTypingIndicator) {
    isTyping.value = true;

    try {
      // Use streaming response for better UX (now with proper CSRF handling)
      await getStreamingAIResponse(message);
      isTyping.value = false;
    } catch (error) {
      console.error('Error getting AI response:', error);
      isTyping.value = false;

      // Fallback to non-streaming response
      try {
        const aiResponse = await getAIResponse(message);
        addBotMessage(aiResponse);
      } catch (fallbackError) {
        console.error('Fallback response also failed:', fallbackError);
        addBotMessage("I apologize, but I'm having trouble processing your request right now. Please try again in a moment.");
      }

      setTimeout(() => {
        scrollToBottom();
      }, 50);
    }
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

// AI-powered response using RillTech Agent
const getAIResponse = async (userMessage: string): Promise<string> => {
  try {
    const axios = (await import('axios')).default;

    const response = await axios.post('/api/chat', {
      message: userMessage,
      session_id: sessionId.value,
      context: {
        page: window.location.pathname,
        timestamp: new Date().toISOString(),
        widget: true,
        isLandingPage: window.location.pathname === '/' || window.location.pathname === '' || window.location.pathname.includes('landing'),
        availableSections: ['hero', 'features', 'pricing', 'about', 'contact']
      }
    }, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    const data = response.data;

    if (data.success) {
      // Store fallback info for the next bot message
      if (data.is_fallback) {
        (window as any).lastResponseFallbackInfo = {
          isFallback: true,
          fallbackReason: data.fallback_reason,
          aiProvider: data.ai_provider
        };
      } else {
        (window as any).lastResponseFallbackInfo = {
          isFallback: false,
          aiProvider: data.ai_provider || 'mistral'
        };
      }
      return data.response;
    } else {
      throw new Error(data.message || 'API request failed');
    }
  } catch (error) {
    console.error('Chat API error:', error);
    return "I apologize, but I'm having trouble processing your request right now. Please try again in a moment.";
  }
};

// Streaming AI response for real-time chat
const getStreamingAIResponse = async (userMessage: string): Promise<void> => {
  try {
    // Get XSRF token from cookie (same way Axios does it)
    const getXSRFToken = () => {
      const cookies = document.cookie.split(';');
      for (let cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') {
          return decodeURIComponent(value);
        }
      }
      return null;
    };

    const xsrfToken = getXSRFToken();

    const response = await fetch('/api/chat/stream', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken || '',
      },
      body: JSON.stringify({
        message: userMessage,
        session_id: sessionId.value,
        context: {
          page: window.location.pathname,
          timestamp: new Date().toISOString(),
          widget: true,
          isLandingPage: window.location.pathname === '/' || window.location.pathname === '' || window.location.pathname.includes('landing'),
          availableSections: ['hero', 'features', 'pricing', 'about', 'contact']
        }
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Create a placeholder bot message that we'll update in real-time
    const botMessage: Message = {
      id: Date.now().toString(),
      content: '',
      sender: 'bot',
      timestamp: new Date(),
      avatar: props.botAvatar,
      isFallback: false,
      aiProvider: 'mistral'
    };

    messages.value.push(botMessage);
    streamingMessageId.value = botMessage.id; // Track which message is streaming
    scrollToBottom();

    // Read the streaming response
    const reader = response.body?.getReader();
    if (!reader) {
      throw new Error('No response body reader available');
    }

    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
      const { done, value } = await reader.read();

      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop() || '';

      for (const line of lines) {
        if (line.startsWith('data: ')) {
          try {
            const data = JSON.parse(line.slice(6));
            if (data.chunk) {
              // Update the bot message content in real-time
              botMessage.content += data.chunk;
              // Trigger reactivity by updating the array
              messages.value = [...messages.value];
              scrollToBottom();
            } else if (data.complete) {
              // Update fallback information when streaming completes
              if (data.is_fallback) {
                botMessage.isFallback = true;
                botMessage.fallbackReason = data.fallback_reason;
                botMessage.aiProvider = data.ai_provider;
              } else {
                botMessage.isFallback = false;
                botMessage.aiProvider = data.ai_provider || 'mistral';
              }
              // Clear streaming ID and trigger reactivity
              streamingMessageId.value = null;
              messages.value = [...messages.value];
              return;
            } else if (data.error) {
              throw new Error(data.error);
            }
          } catch (parseError) {
            console.warn('Failed to parse streaming data:', parseError);
          }
        }
      }
    }
  } catch (error) {
    console.error('Streaming API error:', error);
    throw error; // Re-throw to trigger fallback
  }
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
          <div class="flex items-center gap-1">
            <Button
              @click="resetChat"
              variant="ghost"
              size="sm"
              class="h-8 w-8 p-0"
              title="Reset Chat"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                <path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                <path d="M3 21v-5h5"/>
              </svg>
            </Button>
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
                    : message.isFallback
                    ? 'bg-orange-50 text-orange-900 border border-orange-200 dark:bg-orange-950 dark:text-orange-100 dark:border-orange-800'
                    : 'bg-muted text-foreground'
                ]"
              >
                <!-- Fallback Indicator -->
                <div v-if="message.sender === 'bot' && message.isFallback" class="flex items-center gap-1 mb-2 text-xs opacity-75">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                  </svg>
                  <span>Offline Mode</span>
                </div>

                <!-- Rich text content for bot messages, plain text for user messages -->
                <RichTextMessage
                  v-if="message.sender === 'bot'"
                  :content="message.content"
                  :is-streaming="message.id === streamingMessageId"
                />
                <p v-else>{{ message.content }}</p>

                <div class="flex items-center justify-between mt-1">
                  <p
                    :class="[
                      'text-xs opacity-70',
                      message.sender === 'user' ? 'text-right' : 'text-left'
                    ]"
                  >
                    {{ formatTime(message.timestamp) }}
                  </p>

                  <!-- AI Provider Badge -->
                  <div v-if="message.sender === 'bot'" class="flex items-center gap-1">
                    <Badge
                      :variant="message.isFallback ? 'secondary' : 'outline'"
                      class="text-xs px-1 py-0 h-4"
                    >
                      {{ message.isFallback ? 'Offline' : (message.aiProvider === 'mistral' ? 'AI' : message.aiProvider) }}
                    </Badge>
                  </div>
                </div>
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
