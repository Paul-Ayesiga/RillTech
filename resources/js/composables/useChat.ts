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

    // Simulate typing delay
    await new Promise(resolve => setTimeout(resolve, chatConfig.typingDelay));

    isTyping.value = false;

    // Generate response (replace with actual AI integration)
    const response = generateBotResponse(userMessage);
    return addMessage(response, 'bot');
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

  // API integration (placeholder)
  const sendToAPI = async (message: string): Promise<string> => {
    if (!chatConfig.apiEndpoint) {
      return generateBotResponse(message);
    }

    try {
      const response = await fetch(chatConfig.apiEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          message,
          session_id: currentSession.value?.id
        })
      });

      const data = await response.json();
      return data.response || 'Sorry, I couldn\'t process that request.';
    } catch (error) {
      console.error('Chat API error:', error);
      return 'Sorry, I\'m having trouble connecting right now. Please try again later.';
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

    // Utilities
    formatTimestamp,
    exportChatHistory,
    getMessageStats,
    sendToAPI
  };
}

// Global chat instance
export const globalChat = useChat();
