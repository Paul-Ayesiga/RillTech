import { ref, reactive, computed, readonly } from 'vue';

export interface ChatMessage {
  id: string;
  content: string;
  sender: 'user' | 'bot' | 'system';
  timestamp: Date;
  metadata?: Record<string, any>;
}

export interface ChatSession {
  id: string;
  messages: ChatMessage[];
  isActive: boolean;
  startedAt: Date;
  lastActivity: Date;
}

export interface ChatConfig {
  botName: string;
  botAvatar: string;
  welcomeMessage: string;
  apiEndpoint?: string;
  maxMessages: number;
  typingDelay: number;
  autoRespond: boolean;
}

export function useChat(config: Partial<ChatConfig> = {}) {
  // Default configuration
  const defaultConfig: ChatConfig = {
    botName: 'RillTech Assistant',
    botAvatar: '/images/ai2.jpg',
    welcomeMessage: 'Hi! I\'m your AI assistant. How can I help you today?',
    maxMessages: 100,
    typingDelay: 1500,
    autoRespond: true,
    ...config
  };

  // State
  const isOpen = ref(false);
  const isTyping = ref(false);
  const unreadCount = ref(0);
  const currentSession = ref<ChatSession | null>(null);
  const sessions = ref<ChatSession[]>([]);

  // Reactive config
  const chatConfig = reactive(defaultConfig);

  // Computed
  const hasUnreadMessages = computed(() => unreadCount.value > 0);
  const currentMessages = computed(() => currentSession.value?.messages || []);
  const isActive = computed(() => currentSession.value?.isActive || false);

  // Session management
  const createSession = (): ChatSession => {
    const session: ChatSession = {
      id: Date.now().toString(),
      messages: [],
      isActive: true,
      startedAt: new Date(),
      lastActivity: new Date()
    };

    sessions.value.push(session);
    currentSession.value = session;

    // Add welcome message
    if (chatConfig.welcomeMessage) {
      addMessage(chatConfig.welcomeMessage, 'bot');
    }

    return session;
  };

  const getOrCreateSession = (): ChatSession => {
    if (!currentSession.value) {
      return createSession();
    }
    return currentSession.value;
  };

  const endSession = () => {
    if (currentSession.value) {
      currentSession.value.isActive = false;
      currentSession.value = null;
    }
  };

  // Message management
  const addMessage = (content: string, sender: 'user' | 'bot' | 'system', metadata?: Record<string, any>) => {
    const session = getOrCreateSession();

    const message: ChatMessage = {
      id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      content,
      sender,
      timestamp: new Date(),
      metadata
    };

    session.messages.push(message);
    session.lastActivity = new Date();

    // Limit messages
    if (session.messages.length > chatConfig.maxMessages) {
      session.messages = session.messages.slice(-chatConfig.maxMessages);
    }

    // Update unread count if chat is closed and message is from bot
    if (!isOpen.value && sender === 'bot') {
      unreadCount.value++;
    }

    return message;
  };

  const sendMessage = async (content: string): Promise<ChatMessage> => {
    const userMessage = addMessage(content, 'user');

    // Auto-respond if enabled
    if (chatConfig.autoRespond) {
      await simulateBotResponse(content);
    }

    return userMessage;
  };

  const simulateBotResponse = async (userMessage: string): Promise<ChatMessage> => {
    isTyping.value = true;

    try {
      // Get response from AI API
      const response = await sendToAPI(userMessage);
      isTyping.value = false;
      return addMessage(response, 'bot');
    } catch (error) {
      console.error('Error getting bot response:', error);
      isTyping.value = false;
      // Fallback to simple response
      const fallbackResponse = "I apologize, but I'm having trouble processing your request right now. Please try again in a moment.";
      return addMessage(fallbackResponse, 'bot');
    }
  };

  // Streaming bot response for real-time chat
  const streamBotResponse = async (userMessage: string): Promise<ChatMessage> => {
    isTyping.value = true;

    // Create a placeholder message that we'll update
    const botMessage = addMessage('', 'bot');
    let accumulatedResponse = '';

    try {
      await sendToStreamingAPI(userMessage, (chunk: string) => {
        accumulatedResponse += chunk;
        // Update the message content in real-time
        const messageIndex = currentMessages.value.findIndex(m => m.id === botMessage.id);
        if (messageIndex !== -1) {
          currentMessages.value[messageIndex].content = accumulatedResponse;
        }
      });

      isTyping.value = false;
      return botMessage;
    } catch (error) {
      console.error('Error streaming bot response:', error);
      isTyping.value = false;
      // Update with fallback response
      const messageIndex = currentMessages.value.findIndex(m => m.id === botMessage.id);
      if (messageIndex !== -1) {
        currentMessages.value[messageIndex].content = "I apologize, but I'm having trouble processing your request right now. Please try again in a moment.";
      }
      return botMessage;
    }
  };

  // Chat actions
  const openChat = () => {
    isOpen.value = true;
    unreadCount.value = 0;
    getOrCreateSession();
  };

  const closeChat = () => {
    isOpen.value = false;
  };

  const toggleChat = () => {
    if (isOpen.value) {
      closeChat();
    } else {
      openChat();
    }
  };

  const clearMessages = () => {
    if (currentSession.value) {
      currentSession.value.messages = [];
      if (chatConfig.welcomeMessage) {
        addMessage(chatConfig.welcomeMessage, 'bot');
      }
    }
  };

  // Bot response generation (replace with your AI logic)
  const generateBotResponse = (userMessage: string): string => {
    const lowerMessage = userMessage.toLowerCase();

    // Simple keyword-based responses
    if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
      return 'Hello! How can I assist you today?';
    }

    if (lowerMessage.includes('help')) {
      return 'I\'m here to help! You can ask me about our AI agents, pricing, features, or anything else you\'d like to know.';
    }

    if (lowerMessage.includes('price') || lowerMessage.includes('cost')) {
      return 'We offer flexible pricing plans starting from $29/month. Would you like me to show you our pricing options?';
    }

    if (lowerMessage.includes('feature')) {
      return 'Our AI agents come with powerful features like natural language processing, custom training, and seamless integrations. What specific features are you interested in?';
    }

    if (lowerMessage.includes('demo')) {
      return 'I\'d be happy to arrange a demo for you! You can schedule one using the "Schedule a Demo" button on our homepage, or I can connect you with our sales team.';
    }

    // Default responses
    const responses = [
      "That's interesting! Tell me more about what you're looking for.",
      "I understand. How can I help you with that specifically?",
      "Thanks for sharing! Is there anything particular you'd like to know about our AI agents?",
      "I'm here to help! What would you like to explore about our platform?",
      "Great question! Let me provide you with some information about that.",
      "I'd be happy to assist you with that. Can you tell me more about your needs?",
      "That sounds important. How can our AI agents help you achieve your goals?",
      "I see what you mean. Would you like to learn more about how we can help?"
    ];

    return responses[Math.floor(Math.random() * responses.length)];
  };

  // API integration with RillTech AI Agent
  const sendToAPI = async (message: string): Promise<string> => {
    try {
      const axios = (await import('axios')).default;

      const response = await axios.post('/api/chat', {
        message,
        session_id: currentSession.value?.id,
        context: {
          page: window.location.pathname,
          timestamp: new Date().toISOString(),
        }
      }, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        }
      });

      const data = response.data;

      if (data.success) {
        return data.response || 'Sorry, I couldn\'t process that request.';
      } else {
        throw new Error(data.message || 'API request failed');
      }
    } catch (error) {
      console.error('Chat API error:', error);
      return 'Sorry, I\'m having trouble connecting right now. Please try again later.';
    }
  };

  // Streaming API integration
  const sendToStreamingAPI = async (message: string, onChunk: (chunk: string) => void): Promise<void> => {
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
          message,
          session_id: currentSession.value?.id,
          context: {
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
          }
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

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
                onChunk(data.chunk);
              } else if (data.complete) {
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
      onChunk('Sorry, I\'m having trouble connecting right now. Please try again later.');
    }
  };

  // Utility functions
  const formatTimestamp = (date: Date): string => {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  const exportChatHistory = (): string => {
    if (!currentSession.value) return '';

    return currentSession.value.messages
      .map(msg => `[${formatTimestamp(msg.timestamp)}] ${msg.sender}: ${msg.content}`)
      .join('\n');
  };

  const getMessageStats = () => {
    if (!currentSession.value) return { total: 0, user: 0, bot: 0 };

    const messages = currentSession.value.messages;
    return {
      total: messages.length,
      user: messages.filter(m => m.sender === 'user').length,
      bot: messages.filter(m => m.sender === 'bot').length
    };
  };

  return {
    // State
    isOpen: readonly(isOpen),
    isTyping: readonly(isTyping),
    unreadCount: readonly(unreadCount),
    currentSession: readonly(currentSession),
    sessions: readonly(sessions),
    chatConfig,

    // Computed
    hasUnreadMessages,
    currentMessages,
    isActive,

    // Actions
    openChat,
    closeChat,
    toggleChat,
    sendMessage,
    addMessage,
    clearMessages,
    createSession,
    endSession,

    // AI Integration
    simulateBotResponse,
    streamBotResponse,
    sendToAPI,
    sendToStreamingAPI,

    // Utilities
    formatTimestamp,
    exportChatHistory,
    getMessageStats
  };
}

// Global chat instance
export const globalChat = useChat();
