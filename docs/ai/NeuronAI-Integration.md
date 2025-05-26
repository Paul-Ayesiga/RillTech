# Neuron AI Integration for RillTech Chat Widget

This document explains how the RillTech chat widget has been integrated with Neuron AI to provide intelligent, context-aware responses.

## Overview

The chat widget now uses Neuron AI framework with OpenAI's GPT models to provide intelligent responses about RillTech's platform, features, pricing, and services.

## Architecture

### Backend Components

1. **RillTechAgent** (`app/AI/RillTechAgent.php`)
   - Main AI agent extending Neuron AI's Agent class
   - Configured with OpenAI provider (GPT-4o-mini by default)
   - Includes comprehensive system prompts for RillTech context

2. **AI Tools** (`app/AI/Tools/`)
   - `GetPricingInfo.php` - Provides detailed pricing information
   - `GetFeatureInfo.php` - Explains platform features and capabilities
   - `ScheduleDemo.php` - Helps users schedule demos and contact sales
   - `GetCompanyInfo.php` - Shares company information and mission

3. **ChatController** (`app/Http/Controllers/ChatController.php`)
   - Handles chat API requests
   - Supports both regular and streaming responses
   - Includes error handling and logging

### Frontend Components

1. **Updated ChatWidget** (`resources/js/components/ui/ChatWidget.vue`)
   - Integrated with backend AI API
   - Real-time AI responses
   - Improved error handling

2. **Enhanced useChat Composable** (`resources/js/composables/useChat.ts`)
   - API integration functions
   - Streaming support for real-time responses
   - Session management

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# OpenAI Configuration for Neuron AI
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4o-mini

# Inspector.dev for AI Observability (optional)
INSPECTOR_INGESTION_KEY=your_inspector_key_here
```

### Services Configuration

The integration uses the `config/services.php` configuration:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
],

'inspector' => [
    'ingestion_key' => env('INSPECTOR_INGESTION_KEY'),
],
```

## API Endpoints

### POST /api/chat
Send a message to the AI agent and receive a complete response.

**Request:**
```json
{
    "message": "What are your pricing plans?",
    "session_id": "optional_session_id",
    "context": {
        "page": "/pricing",
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

**Response:**
```json
{
    "success": true,
    "response": "Here are our current pricing plans...",
    "session_id": "session_12345",
    "timestamp": "2024-01-01T00:00:00Z"
}
```

### POST /api/chat/stream
Stream AI responses in real-time for better user experience.

**Request:** Same as above

**Response:** Server-Sent Events stream with chunks of the response.

## AI Agent Capabilities

The RillTech AI Agent can help users with:

1. **Pricing Information**
   - Detailed plan comparisons
   - Feature breakdowns
   - Custom pricing for enterprise

2. **Feature Explanations**
   - Drag & drop builder
   - AI model integrations
   - Platform capabilities
   - Security features

3. **Demo Scheduling**
   - General platform demos
   - Enterprise-focused sessions
   - Feature-specific demonstrations

4. **Company Information**
   - Mission and values
   - Technology overview
   - Security and compliance
   - Team information

## Usage Examples

### Basic Chat Integration

```typescript
// Using the chat widget
const response = await getAIResponse("Tell me about your enterprise features");
```

### Streaming Responses

```typescript
// Real-time streaming
await sendToStreamingAPI(message, (chunk) => {
    // Update UI with each chunk
    updateMessageContent(chunk);
});
```

## Monitoring and Observability

The integration includes optional monitoring through Inspector.dev:

- Real-time agent performance tracking
- Conversation flow analysis
- Error monitoring and debugging
- Usage analytics

## Error Handling

The system includes comprehensive error handling:

1. **API Errors**: Graceful fallback responses
2. **Network Issues**: Retry mechanisms and user feedback
3. **Rate Limiting**: Proper error messages and guidance
4. **Invalid Requests**: Input validation and sanitization

## Security Considerations

- All API requests include CSRF protection
- Input validation and sanitization
- Rate limiting on chat endpoints
- Secure API key management
- No sensitive data in logs

## Performance Optimization

- Efficient streaming for real-time responses
- Caching of common responses
- Optimized API payload sizes
- Background processing for complex queries

## Future Enhancements

Planned improvements include:

1. **Memory Persistence**: Remember conversation context across sessions
2. **Advanced Tools**: Integration with CRM and support systems
3. **Multilingual Support**: Multiple language capabilities
4. **Voice Integration**: Speech-to-text and text-to-speech
5. **Analytics Dashboard**: Detailed conversation analytics

## Troubleshooting

### Common Issues

1. **API Key Not Working**
   - Verify OpenAI API key is valid
   - Check environment variable configuration
   - Ensure sufficient API credits

2. **Slow Responses**
   - Check network connectivity
   - Verify OpenAI service status
   - Consider using streaming for better UX

3. **Error Messages**
   - Check Laravel logs for detailed errors
   - Verify all dependencies are installed
   - Ensure proper configuration

### Debug Mode

Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file to see detailed API interactions.
