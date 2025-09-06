# ChatWidget Component

A modern, interactive chat widget with AI assistant capabilities, perfect for customer support and user engagement on your landing page.

## Features

- 💬 **Interactive Chat**: Full-featured chat interface with message history
- 🤖 **AI Assistant**: Intelligent bot responses with typing indicators
- 🎨 **Customizable**: Flexible positioning, styling, and configuration
- 📱 **Responsive**: Works perfectly on all screen sizes
- 🔔 **Notifications**: Unread message badges and visual indicators
- ⌨️ **Keyboard Support**: Enter to send, proper focus management
- 🎭 **Smooth Animations**: Polished transitions and micro-interactions
- 📊 **Message Management**: Auto-scroll, message limits, timestamps
- 🎯 **Event System**: Comprehensive event callbacks for integration
- ♿ **Accessible**: ARIA labels and keyboard navigation

## Usage

### Basic Usage

```vue
<template>
  <ChatWidget />
</template>
```

### Advanced Configuration

```vue
<template>
  <ChatWidget
    position="bottom-left"
    bot-name="RillTech Assistant"
    bot-avatar="/images/ai2.jpg"
    welcome-message="Hi! How can I help you today?"
    placeholder="Ask me anything..."
    :max-messages="50"
    :auto-open="false"
    :show-typing-indicator="true"
    @message-sent="onMessageSent"
    @chat-opened="onChatOpened"
    @chat-closed="onChatClosed"
  />
</template>

<script setup>
const onMessageSent = (message) => {
  console.log('User sent:', message);
  // Send to your AI service
};

const onChatOpened = () => {
  console.log('Chat opened');
  // Track analytics
};

const onChatClosed = () => {
  console.log('Chat closed');
  // Save session data
};
</script>
```

### Using the Composable

```vue
<script setup>
import { useChat } from '@/composables/useChat';

const {
  isOpen,
  currentMessages,
  sendMessage,
  openChat,
  closeChat,
  clearMessages
} = useChat({
  botName: 'Custom Assistant',
  welcomeMessage: 'Welcome!',
  autoRespond: true
});

// Send message programmatically
await sendMessage('Hello from code!');

// Open chat programmatically
openChat();

// Get message statistics
const stats = getMessageStats();
console.log(`Total: ${stats.total}, User: ${stats.user}, Bot: ${stats.bot}`);
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `position` | `'bottom-right' \| 'bottom-left'` | `'bottom-right'` | Widget position |
| `botName` | `string` | `'RillTech Assistant'` | Bot display name |
| `botAvatar` | `string` | `'/images/ai2.jpg'` | Bot avatar image URL |
| `welcomeMessage` | `string` | `'Hi! I\'m your AI assistant...'` | Initial bot message |
| `placeholder` | `string` | `'Type your message...'` | Input placeholder text |
| `primaryColor` | `string` | `'hsl(var(--primary))'` | Primary color override |
| `maxMessages` | `number` | `100` | Maximum messages to keep |
| `autoOpen` | `boolean` | `false` | Auto-open chat on load |
| `showTypingIndicator` | `boolean` | `true` | Show typing animation |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `@message-sent` | `string` | User sent a message |
| `@chat-opened` | `void` | Chat window opened |
| `@chat-closed` | `void` | Chat window closed |

## Composable API

### Configuration

```typescript
interface ChatConfig {
  botName: string;
  botAvatar: string;
  welcomeMessage: string;
  apiEndpoint?: string;
  maxMessages: number;
  typingDelay: number;
  autoRespond: boolean;
}
```

### Methods

- `openChat()` - Open the chat window
- `closeChat()` - Close the chat window
- `toggleChat()` - Toggle chat window state
- `sendMessage(content)` - Send a message
- `addMessage(content, sender, metadata?)` - Add message manually
- `clearMessages()` - Clear message history
- `createSession()` - Create new chat session
- `endSession()` - End current session

### Reactive Properties

- `isOpen` - Chat window open state
- `isTyping` - Bot typing indicator state
- `unreadCount` - Number of unread messages
- `currentMessages` - Array of current messages
- `currentSession` - Current chat session data

### Utilities

- `formatTimestamp(date)` - Format message timestamp
- `exportChatHistory()` - Export chat as text
- `getMessageStats()` - Get message statistics
- `sendToAPI(message)` - Send to external API

## Styling

### Position Options

- **Bottom Right**: `position="bottom-right"` - Default position
- **Bottom Left**: `position="bottom-left"` - Alternative position

### Custom Styling

```vue
<style>
/* Override chat button */
.chat-widget-button {
  background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
}

/* Custom message bubbles */
.chat-message-user {
  background: hsl(var(--primary));
}

.chat-message-bot {
  background: hsl(var(--muted));
}

/* Custom typing indicator */
.typing-indicator {
  animation: pulse 1.5s ease-in-out infinite;
}
</style>
```

## Integration

### Landing Page Setup

The ChatWidget is integrated into the landing page with optimal configuration:

```vue
<ChatWidget
  position="bottom-left"
  bot-name="RillTech Assistant"
  bot-avatar="/images/ai2.jpg"
  welcome-message="Hi! I'm your AI assistant. I can help you learn about our AI agents, pricing, features, and answer any questions you have. How can I help you today?"
  placeholder="Ask me anything about RillTech..."
  :max-messages="50"
  :auto-open="false"
  :show-typing-indicator="true"
/>
```

### AI Service Integration

Replace the `generateBotResponse` function with your AI service:

```javascript
// In useChat.ts
const sendToAPI = async (message) => {
  const response = await fetch('/api/chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message, session_id: currentSession.value?.id })
  });
  
  const data = await response.json();
  return data.response;
};
```

### Backend Integration

Example Laravel controller:

```php
// ChatController.php
public function sendMessage(Request $request)
{
    $message = $request->input('message');
    $sessionId = $request->input('session_id');
    
    // Process with your AI service
    $response = $this->aiService->generateResponse($message);
    
    return response()->json(['response' => $response]);
}
```

## Customization

### Bot Responses

Customize bot responses in the `generateBotResponse` function:

```javascript
const generateBotResponse = (userMessage) => {
  const lowerMessage = userMessage.toLowerCase();
  
  if (lowerMessage.includes('pricing')) {
    return 'Our plans start at $29/month. Would you like to see our pricing page?';
  }
  
  if (lowerMessage.includes('demo')) {
    return 'I can schedule a demo for you! What time works best?';
  }
  
  // Add more custom responses
  return 'I understand. How can I help you with that?';
};
```

### Message Templates

Create reusable message templates:

```javascript
const messageTemplates = {
  greeting: 'Hello! Welcome to RillTech. How can I assist you?',
  pricing: 'Our flexible pricing starts at $29/month. View pricing →',
  demo: 'Schedule a personalized demo to see our AI agents in action!',
  features: 'Our AI agents offer NLP, custom training, and integrations.'
};
```

## Performance

- **Efficient Rendering**: Virtual scrolling for large message lists
- **Memory Management**: Automatic message cleanup after limits
- **Throttled Events**: Optimized scroll and typing events
- **Lazy Loading**: Components loaded on demand
- **Minimal Bundle**: Tree-shakeable composable functions

## Accessibility

- **Keyboard Navigation**: Full keyboard support
- **Screen Readers**: Proper ARIA labels and roles
- **Focus Management**: Logical tab order
- **High Contrast**: Supports system preferences
- **Reduced Motion**: Respects user motion preferences

## Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Messages not appearing**: Check console for errors, verify bot avatar path
2. **Styling conflicts**: Ensure proper z-index and positioning
3. **Performance issues**: Reduce maxMessages limit, check for memory leaks
4. **Mobile layout**: Test responsive behavior, adjust positioning if needed

### Debug Mode

Enable debug logging:

```javascript
const chat = useChat({
  debug: true // Enables console logging
});
```
