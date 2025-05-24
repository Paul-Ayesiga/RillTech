<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ChatWidget from '@/components/ui/ChatWidget.vue';
import { useChat } from '@/composables/useChat';

const { 
  isOpen, 
  isTyping, 
  unreadCount, 
  currentMessages, 
  openChat, 
  closeChat, 
  sendMessage, 
  clearMessages,
  getMessageStats 
} = useChat();

const messageStats = ref(getMessageStats());

const onMessageSent = (message: string) => {
  console.log('Message sent:', message);
  // Update stats after a short delay to account for bot response
  setTimeout(() => {
    messageStats.value = getMessageStats();
  }, 2000);
};

const onChatOpened = () => {
  console.log('Chat opened');
};

const onChatClosed = () => {
  console.log('Chat closed');
};

const triggerBotMessage = () => {
  // Simulate receiving a message from the bot
  const messages = [
    "Hello! I noticed you're exploring our chat features. How can I help?",
    "Did you know our AI agents can be customized for your specific needs?",
    "Would you like to schedule a demo to see our platform in action?",
    "I'm here to answer any questions about our AI technology!"
  ];
  
  const randomMessage = messages[Math.floor(Math.random() * messages.length)];
  // This would typically come from your backend/AI service
  console.log('Simulated bot message:', randomMessage);
};

const clearChatHistory = () => {
  clearMessages();
  messageStats.value = getMessageStats();
};
</script>

<template>
  <div class="px-3 space-y-6">
    <!-- Chat Widget Demo -->
    <Card>
      <CardHeader>
        <CardTitle>Chat Widget Component</CardTitle>
        <CardDescription>
          An interactive chat widget with AI assistant capabilities, typing indicators, and message management.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Features Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold mb-3">Features:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Floating chat button with unread badge</li>
              <li>Expandable chat window</li>
              <li>Typing indicators</li>
              <li>Message timestamps</li>
              <li>Bot avatar and user identification</li>
              <li>Smooth animations and transitions</li>
              <li>Responsive design</li>
              <li>Keyboard shortcuts (Enter to send)</li>
              <li>Auto-scroll to latest messages</li>
              <li>Customizable appearance</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-3">Configuration:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-muted-foreground">
              <li>Position: Bottom left</li>
              <li>Bot: RillTech Assistant</li>
              <li>Avatar: AI agent image</li>
              <li>Max messages: 50</li>
              <li>Auto-open: Disabled</li>
              <li>Typing indicator: Enabled</li>
              <li>Welcome message: Custom</li>
              <li>Placeholder: Custom</li>
            </ul>
          </div>
        </div>

        <!-- Chat Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="text-center p-3 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-primary">{{ messageStats.total }}</div>
            <div class="text-xs text-muted-foreground">Total Messages</div>
          </div>
          <div class="text-center p-3 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ messageStats.user }}</div>
            <div class="text-xs text-muted-foreground">User Messages</div>
          </div>
          <div class="text-center p-3 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ messageStats.bot }}</div>
            <div class="text-xs text-muted-foreground">Bot Messages</div>
          </div>
          <div class="text-center p-3 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-orange-600">{{ unreadCount }}</div>
            <div class="text-xs text-muted-foreground">Unread</div>
          </div>
        </div>

        <!-- Chat Status -->
        <div class="flex items-center gap-4">
          <Badge :variant="isOpen ? 'default' : 'secondary'">
            {{ isOpen ? 'Chat Open' : 'Chat Closed' }}
          </Badge>
          <Badge v-if="isTyping" variant="outline" class="animate-pulse">
            Bot is typing...
          </Badge>
        </div>

        <!-- Demo Controls -->
        <div class="space-y-4">
          <h4 class="font-semibold">Demo Controls:</h4>
          <div class="flex flex-wrap gap-3">
            <Button @click="openChat" variant="default" :disabled="isOpen">
              Open Chat
            </Button>
            <Button @click="closeChat" variant="outline" :disabled="!isOpen">
              Close Chat
            </Button>
            <Button @click="triggerBotMessage" variant="secondary">
              Trigger Bot Message
            </Button>
            <Button @click="clearChatHistory" variant="destructive">
              Clear History
            </Button>
          </div>
        </div>

        <!-- Usage Examples -->
        <div class="space-y-4">
          <h4 class="font-semibold">Usage Examples:</h4>
          <div class="space-y-3 text-sm">
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">Basic Usage:</p>
              <code class="text-xs">&lt;ChatWidget /&gt;</code>
            </div>
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">Custom Configuration:</p>
              <code class="text-xs">
                &lt;ChatWidget position="bottom-right" bot-name="Assistant" :auto-open="true" /&gt;
              </code>
            </div>
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">With Events:</p>
              <code class="text-xs">
                &lt;ChatWidget @message-sent="onMessage" @chat-opened="onOpen" /&gt;
              </code>
            </div>
            <div class="p-3 bg-muted rounded-md">
              <p class="font-medium mb-1">Using Composable:</p>
              <code class="text-xs">
                const { sendMessage, openChat, currentMessages } = useChat();
              </code>
            </div>
          </div>
        </div>

        <!-- Integration Notes -->
        <div class="p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Integration Notes:</h4>
          <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
            <li>• The chat widget is positioned on the bottom-left of the landing page</li>
            <li>• ScrollToTop button is on bottom-right to avoid conflicts</li>
            <li>• Bot responses are currently simulated - integrate with your AI service</li>
            <li>• Messages persist during the session but reset on page reload</li>
            <li>• Customize the bot responses in the generateBotResponse function</li>
          </ul>
        </div>

        <!-- Test Messages -->
        <div class="space-y-4">
          <h4 class="font-semibold">Test the Chat:</h4>
          <p class="text-sm text-muted-foreground">
            Try sending these messages to see different bot responses:
          </p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <Badge variant="outline" class="cursor-pointer hover:bg-muted" @click="sendMessage('Hello')">
              "Hello"
            </Badge>
            <Badge variant="outline" class="cursor-pointer hover:bg-muted" @click="sendMessage('Help')">
              "Help"
            </Badge>
            <Badge variant="outline" class="cursor-pointer hover:bg-muted" @click="sendMessage('Pricing')">
              "Pricing"
            </Badge>
            <Badge variant="outline" class="cursor-pointer hover:bg-muted" @click="sendMessage('Demo')">
              "Demo"
            </Badge>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Chat Widget (for this demo page) -->
    <ChatWidget
      position="bottom-right"
      bot-name="Demo Assistant"
      bot-avatar="/images/ai2.jpg"
      welcome-message="Hi! This is a demo of the chat widget. Try sending me a message!"
      placeholder="Type your message here..."
      :max-messages="20"
      :auto-open="false"
      :show-typing-indicator="true"
      @message-sent="onMessageSent"
      @chat-opened="onChatOpened"
      @chat-closed="onChatClosed"
    />
  </div>
</template>
