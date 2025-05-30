<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\AI\RillTechAgent;
use NeuronAI\Chat\Messages\UserMessage;

class ChatController extends Controller
{
    /**
     * Send a message to the AI agent and get a response
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'session_id' => 'nullable|string|max:100',
                'context' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $userMessage = $validated['message'];
            $sessionId = $validated['session_id'] ?? 'session_' . uniqid();
            $context = $validated['context'] ?? [];

            // Log the chat interaction
            Log::info('Chat message received', [
                'session_id' => $sessionId,
                'message' => $userMessage,
                'message_length' => strlen($userMessage),
                'context' => $context,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log OpenAI configuration
            Log::info('OpenAI Configuration Check', [
                'api_key_configured' => !empty(config('services.openai.api_key')),
                'api_key_length' => strlen(config('services.openai.api_key') ?? ''),
                'model' => config('services.openai.model'),
                'inspector_configured' => !empty(config('services.inspector.ingestion_key')),
            ]);

            // Create the AI agent with built-in memory management
            Log::info('Creating RillTech AI Agent with session', ['session_id' => $sessionId]);
            $agent = $this->createAgent($sessionId);
            Log::info('AI Agent created successfully with enhanced capabilities');

            // Add observability if Inspector is configured
            // Note: Inspector observability temporarily disabled for debugging
            // if (config('services.inspector.ingestion_key')) {
            //     try {
            //         $inspector = new \Inspector\Inspector(
            //             new \Inspector\Configuration(config('services.inspector.ingestion_key'))
            //         );
            //         $agent->observe(new AgentMonitoring($inspector));
            //         Log::info('Inspector observability added');
            //     } catch (\Exception $e) {
            //         Log::warning('Failed to add Inspector observability', ['error' => $e->getMessage()]);
            //     }
            // }

            // Send message to RAG agent - Try chat() method for tool execution
            Log::info('Sending message to RAG agent', ['message' => $userMessage]);

            // Try chat() method first for tool execution, fallback to answer() for RAG
            try {
                if (method_exists($agent, 'chat')) {
                    $response = $agent->chat(new UserMessage($userMessage));
                } else {
                    $response = $agent->answer(new UserMessage($userMessage));
                }
                $botResponse = $response->getContent();

                // Check if response contains tool call JSON and handle it
                if (str_contains($botResponse, '{"name":') && str_contains($botResponse, '"arguments":')) {
                    Log::info('Tool call detected in response, attempting to execute');
                    $botResponse = $this->handleToolCall($botResponse, $sessionId);
                }

                Log::info('AI response received', ['response_length' => strlen($botResponse)]);
            } catch (\Exception $e) {
                Log::error('Error in agent communication', ['error' => $e->getMessage()]);
                throw $e;
            }

            // Log the successful response
            Log::info('Chat response generated', [
                'session_id' => $sessionId,
                'response_length' => strlen($botResponse),
                'processing_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ]);

            return response()->json([
                'success' => true,
                'response' => $botResponse,
                'session_id' => $sessionId,
                'timestamp' => now()->toISOString(),
                'is_fallback' => false,
                'ai_provider' => 'mistral',
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Chat error occurred', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'session_id' => $sessionId ?? null,
                'message' => $userMessage ?? null,
            ]);

            // Check for specific OpenAI errors
            $errorMessage = 'I apologize, but I\'m having trouble processing your request right now. Please try again in a moment.';
            $errorCode = 'CHAT_ERROR';

            if (str_contains($e->getMessage(), 'RATE_LIMITED') || str_contains($e->getMessage(), 'system is currently busy')) {
                $errorMessage = '🤖 **System Busy** - I\'m currently processing many requests. Please try again in a moment!';
                $errorCode = 'RATE_LIMITED';
            } elseif (str_contains($e->getMessage(), 'QUOTA_EXCEEDED')) {
                $errorMessage = '🤖 **System Maintenance** - Our AI service is temporarily unavailable. Please try again later!';
                $errorCode = 'QUOTA_EXCEEDED';
            } elseif (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'rate limit')) {
                $errorMessage = 'I\'m currently experiencing high demand. Our AI service is temporarily unavailable, but I can still help you with basic information about RillTech!';
                $errorCode = 'RATE_LIMITED';

                // Try to provide a helpful fallback response
                $fallbackResponse = $this->getFallbackResponse($userMessage ?? '', $context ?? []);
                if ($fallbackResponse) {
                    return response()->json([
                        'success' => true,
                        'response' => $fallbackResponse,
                        'session_id' => $sessionId ?? 'fallback_' . uniqid(),
                        'timestamp' => now()->toISOString(),
                        'is_fallback' => true,
                        'fallback_reason' => 'rate_limited',
                        'ai_provider' => 'fallback',
                    ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                      ->header('Pragma', 'no-cache')
                      ->header('Expires', '0');
                }
            } elseif (str_contains($e->getMessage(), 'Mistral') && (str_contains($e->getMessage(), '422') || str_contains($e->getMessage(), 'Unprocessable Entity'))) {
                // Only treat 422 as AI service error if it's from Mistral API, not validation errors
                $errorMessage = 'AI service is being optimized. Let me help you with RillTech information instead!';
                $errorCode = 'API_FORMAT_ERROR';

                // Provide fallback for Mistral API 422 errors
                $fallbackResponse = $this->getFallbackResponse($userMessage ?? '', $context ?? []);
                if ($fallbackResponse) {
                    return response()->json([
                        'success' => true,
                        'response' => $fallbackResponse,
                        'session_id' => $sessionId ?? 'fallback_' . uniqid(),
                        'timestamp' => now()->toISOString(),
                        'is_fallback' => true,
                        'fallback_reason' => 'api_format_error',
                        'ai_provider' => 'fallback',
                    ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                      ->header('Pragma', 'no-cache')
                      ->header('Expires', '0');
                }
            } elseif (str_contains($e->getMessage(), 'API key') || str_contains($e->getMessage(), 'authentication')) {
                $errorMessage = 'AI service is currently being configured. Please try again later or contact support.';
                $errorCode = 'API_CONFIG_ERROR';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $errorCode,
            ], 500);
        }
    }

    /**
     * Provide fallback responses when AI is unavailable
     * Only shows company information to avoid outdated pricing/features
     */
    private function getFallbackResponse(string $userMessage, array $context = []): ?string
    {
        $lowerMessage = strtolower($userMessage);
        $currentPage = $context['page'] ?? '/';

        // Pricing related queries - redirect to pricing section
        if (str_contains($lowerMessage, 'price') || str_contains($lowerMessage, 'cost') || str_contains($lowerMessage, 'plan')) {
            return $this->getPageGuidanceResponse('pricing', $currentPage);
        }

        // Feature related queries - redirect to features section
        if (str_contains($lowerMessage, 'feature') || str_contains($lowerMessage, 'what') || str_contains($lowerMessage, 'how')) {
            return $this->getPageGuidanceResponse('features', $currentPage);
        }

        // Demo/contact related queries
        if (str_contains($lowerMessage, 'demo') || str_contains($lowerMessage, 'contact') || str_contains($lowerMessage, 'schedule')) {
            return $this->getPageGuidanceResponse('contact', $currentPage);
        }

        // Company/about queries - only show company information
        if (str_contains($lowerMessage, 'about') || str_contains($lowerMessage, 'company') || str_contains($lowerMessage, 'who')) {
            return "🤖 **Offline Mode** - **About RillTech:**\n\n" .
                   "We're a cutting-edge platform that democratizes AI agent creation through our intuitive no-code drag-and-drop interface. 🚀\n\n" .
                   "**Our Mission:** Make AI accessible, intuitive, and powerful for everyone - from startups to Fortune 500 companies.\n\n" .
                   "**What we do:**\n" .
                   "• Enable businesses to build AI assistants in minutes, not months\n" .
                   "• Provide enterprise-grade security and scalability\n" .
                   "• Bridge the gap between complex AI technology and practical business solutions\n\n" .
                   "For detailed features and pricing, please check the relevant sections on this page. Our AI assistant will be back online shortly!";
        }

        // Greeting responses - updated to avoid mentioning specific pricing/features
        if (str_contains($lowerMessage, 'hello') || str_contains($lowerMessage, 'hi') || str_contains($lowerMessage, 'hey')) {
            return "🤖 **Offline Mode** - Hello! 👋 I'm the RillTech Assistant.\n\n" .
                   "While our AI assistant is temporarily offline, you can find information about:\n" .
                   "📋 **Features** - Check the Features section on this page\n" .
                   "💰 **Pricing** - View our Pricing section for current plans\n" .
                   "📞 **Contact** - Find our contact information in the Contact section\n" .
                   "🏢 **About Us** - Learn about our company and mission\n\n" .
                   "Our AI assistant will be back online shortly to provide personalized assistance!";
        }

        // Default helpful response - guide to page sections
        return "🤖 **Offline Mode** - Thanks for your question! While our AI assistant is temporarily unavailable, you can find information on this page:\n\n" .
               "📋 **Features Section** - Detailed platform capabilities\n" .
               "💰 **Pricing Section** - Current plans and pricing\n" .
               "📞 **Contact Section** - Get in touch with our team\n" .
               "🏢 **About Section** - Learn about RillTech\n\n" .
               "Our AI assistant will be back online shortly to provide personalized assistance! You can also contact our team directly at hello@rilltech.com.";
    }

    /**
     * Get page-specific guidance response based on current page context
     */
    private function getPageGuidanceResponse(string $section, string $currentPage): string
    {
        $sectionInfo = [
            'pricing' => [
                'title' => 'Pricing Plans',
                'description' => 'current pricing plans and features',
                'emoji' => '💰',
                'action' => 'scroll down to the **Pricing** section'
            ],
            'features' => [
                'title' => 'Platform Features',
                'description' => 'detailed platform capabilities and features',
                'emoji' => '🚀',
                'action' => 'scroll down to the **Features** section'
            ],
            'contact' => [
                'title' => 'Contact Us',
                'description' => 'demo scheduling and contact information',
                'emoji' => '📞',
                'action' => 'scroll down to the **Contact** section'
            ]
        ];

        $info = $sectionInfo[$section] ?? $sectionInfo['features'];

        // Check if user is on the landing page (SPA with sections)
        $isOnLandingPage = $currentPage === '/' || $currentPage === '' || str_contains($currentPage, 'landing');

        // Special handling for demo/contact requests
        if ($section === 'contact') {
            $demoGuidance = "\n\n**📅 For Demo Requests:**\n" .
                           "When contacting us, please use the subject line **\"Demo Request\"** and specify:\n" .
                           "• **General Demo** - Platform overview\n" .
                           "• **Enterprise Demo** - Advanced features\n" .
                           "• **Feature Demo** - Specific capabilities\n" .
                           "• **Technical Demo** - Integration details";
        } else {
            $demoGuidance = "";
        }

        if ($isOnLandingPage) {
            return "🤖 **Offline Mode** - For {$info['description']}, please {$info['action']} on this page {$info['emoji']}\n\n" .
                   "You'll find the most up-to-date information there. Our AI assistant will be back online shortly to provide personalized assistance!{$demoGuidance}\n\n" .
                   "**Need immediate help?** Contact our team at hello@rilltech.com";
        } else {
            return "🤖 **Offline Mode** - For {$info['description']}, please visit our main page and {$info['action']} {$info['emoji']}\n\n" .
                   "You'll find the most current information there. Our AI assistant will be back online shortly to provide personalized assistance!{$demoGuidance}\n\n" .
                   "**Need immediate help?** Contact our team at hello@rilltech.com";
        }
    }

    /**
     * Stream a message to the AI agent for real-time responses
     */
    public function streamMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'session_id' => 'nullable|string|max:100',
                'context' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $userMessage = $validated['message'];
            $sessionId = $validated['session_id'] ?? 'session_' . uniqid();
            $context = $validated['context'] ?? [];

            // Log the streaming chat interaction
            Log::info('Streaming chat message received', [
                'session_id' => $sessionId,
                'message_length' => strlen($userMessage),
                'context' => $context,
                'ip' => $request->ip(),
            ]);

            // Set headers for Server-Sent Events
            $headers = [
                'Content-Type' => 'text/plain',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
            ];

            return response()->stream(function () use ($userMessage, $sessionId) {
                try {
                    // Create the AI agent with built-in memory management
                    Log::info('Creating RillTech AI Agent for streaming', ['session_id' => $sessionId]);
                    $agent = $this->createAgent($sessionId);

                    // Add observability if Inspector is configured
                    // Note: Inspector observability temporarily disabled for debugging
                    // if (config('services.inspector.ingestion_key')) {
                    //     try {
                    //         $inspector = new \Inspector\Inspector(
                    //             new \Inspector\Configuration(config('services.inspector.ingestion_key'))
                    //         );
                    //         $agent->observe(new AgentMonitoring($inspector));
                    //     } catch (\Exception $e) {
                    //         Log::warning('Failed to add Inspector observability', ['error' => $e->getMessage()]);
                    //     }
                    // }

                    // Use regular chat with timeout handling to avoid Mistral API issues
                    set_time_limit(45); // 45 seconds max for the entire operation
                    $startTime = time();

                    // Try chat() method first for tool execution, fallback to answer() for RAG
                    if (method_exists($agent, 'chat')) {
                        $response = $agent->chat(new UserMessage($userMessage));
                    } else {
                        $response = $agent->answer(new UserMessage($userMessage));
                    }
                    $content = $response->getContent();

                    // Check if response contains tool call JSON and handle it
                    if (str_contains($content, '{"name":') && str_contains($content, '"arguments":')) {
                        Log::info('Tool call detected in streaming response, attempting to execute');
                        $content = $this->handleToolCall($content, $sessionId);
                    }

                    // Check if the operation took too long
                    $duration = time() - $startTime;
                    if ($duration > 30) {
                        Log::warning('Chat operation took longer than expected', [
                            'duration' => $duration,
                            'session_id' => $sessionId
                        ]);
                    }

                    // Simulate streaming by sending the response in chunks
                    $words = explode(' ', $content);
                    foreach ($words as $word) {
                        echo "data: " . json_encode(['chunk' => $word . ' ']) . "\n\n";
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                        usleep(30000); // 30ms delay between words for streaming effect
                    }

                    // Send completion signal with AI provider info
                    echo "data: " . json_encode([
                        'complete' => true,
                        'session_id' => $sessionId,
                        'is_fallback' => false,
                        'ai_provider' => 'mistral'
                    ]) . "\n\n";
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();

                } catch (\Exception $e) {
                    Log::error('Streaming chat error', [
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'session_id' => $sessionId,
                    ]);

                    // Try to provide fallback response for streaming
                    $fallbackReason = 'unknown_error';
                    if (str_contains($e->getMessage(), 'RATE_LIMITED') || str_contains($e->getMessage(), 'system is currently busy')) {
                        $fallbackReason = 'rate_limited';
                        $fallbackResponse = "🤖 **System Busy** - I'm currently processing many requests. Please try again in a moment!\n\n" .
                                          "While you wait, you can explore the information on this page:\n\n" .
                                          "📋 **Features Section** - Platform capabilities\n" .
                                          "💰 **Pricing Section** - Current plans\n" .
                                          "📞 **Contact Section** - Get in touch with our team\n\n" .
                                          "I'll be ready to help again shortly! ⏰";
                    } elseif (str_contains($e->getMessage(), 'QUOTA_EXCEEDED')) {
                        $fallbackReason = 'quota_exceeded';
                        $fallbackResponse = "🤖 **System Maintenance** - Our AI service is temporarily unavailable. Please try again later!\n\n" .
                                          "In the meantime, you can find information on this page:\n\n" .
                                          "📋 **Features Section** - Platform capabilities\n" .
                                          "💰 **Pricing Section** - Current plans\n" .
                                          "📞 **Contact Section** - Get in touch with our team\n\n" .
                                          "Thank you for your patience! 🙏";
                    } elseif (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'rate limit')) {
                        $fallbackReason = 'rate_limited';
                        $fallbackResponse = $this->getFallbackResponse($userMessage, $context ?? []);
                        if ($fallbackResponse) {
                            // Stream the fallback response word by word
                            $words = explode(' ', $fallbackResponse);
                            foreach ($words as $word) {
                                echo "data: " . json_encode(['chunk' => $word . ' ']) . "\n\n";
                                if (ob_get_level()) {
                                    ob_flush();
                                }
                                flush();
                                usleep(50000); // 50ms delay between words
                            }
                        }
                    } elseif (str_contains($e->getMessage(), '422')) {
                        $fallbackReason = 'api_format_error';
                        $fallbackResponse = $this->getFallbackResponse($userMessage, $context ?? []);
                        if ($fallbackResponse) {
                            // Stream the fallback response word by word
                            $words = explode(' ', $fallbackResponse);
                            foreach ($words as $word) {
                                echo "data: " . json_encode(['chunk' => $word . ' ']) . "\n\n";
                                if (ob_get_level()) {
                                    ob_flush();
                                }
                                flush();
                                usleep(50000); // 50ms delay between words
                            }
                        }
                    } elseif (str_contains($e->getMessage(), 'Unexpected role') || str_contains($e->getMessage(), '400 Bad Request')) {
                        $fallbackReason = 'conversation_error';
                        // Clear the corrupted conversation history
                        Log::warning('Conversation history corrupted, clearing session', [
                            'session_id' => $sessionId,
                            'error' => $e->getMessage()
                        ]);
                        $this->clearChatHistory($sessionId);
                        $fallbackResponse = $this->getFallbackResponse($userMessage, $context ?? []);
                        if ($fallbackResponse) {
                            // Stream the fallback response word by word
                            $words = explode(' ', $fallbackResponse);
                            foreach ($words as $word) {
                                echo "data: " . json_encode(['chunk' => $word . ' ']) . "\n\n";
                                if (ob_get_level()) {
                                    ob_flush();
                                }
                                flush();
                                usleep(50000); // 50ms delay between words
                            }
                        }
                    } elseif (str_contains($e->getMessage(), 'cURL error') || str_contains($e->getMessage(), 'Empty reply') || str_contains($e->getMessage(), 'Connection timed out') || str_contains($e->getMessage(), 'timeout')) {
                        $fallbackReason = 'connection_error';
                        Log::warning('API connection/timeout error, providing fallback', [
                            'session_id' => $sessionId,
                            'error' => $e->getMessage(),
                            'error_type' => 'timeout_or_connection'
                        ]);

                        // Provide a more specific fallback message for connection issues
                        $fallbackResponse = "🤖 **Offline Mode** - I'm experiencing connectivity issues right now, but I'm here to help!\n\n" .
                                          "While our AI assistant is temporarily unavailable, you can find information on this page:\n\n" .
                                          "📋 **Features Section** - Detailed platform capabilities\n" .
                                          "💰 **Pricing Section** - Current plans and pricing\n" .
                                          "📞 **Contact Section** - Get in touch with our team\n" .
                                          "🏢 **About Section** - Learn about RillTech\n\n" .
                                          "**Need immediate help?** Contact our team directly at hello@rilltech.com\n\n" .
                                          "I'll be back online shortly!";

                        if ($fallbackResponse) {
                            // Stream the fallback response word by word
                            $words = explode(' ', $fallbackResponse);
                            foreach ($words as $word) {
                                echo "data: " . json_encode(['chunk' => $word . ' ']) . "\n\n";
                                if (ob_get_level()) {
                                    ob_flush();
                                }
                                flush();
                                usleep(50000); // 50ms delay between words
                            }
                        }
                    } else {
                        echo "data: " . json_encode([
                            'chunk' => 'I apologize, but I\'m having trouble processing your request right now. Please try again in a moment.'
                        ]) . "\n\n";
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }

                    // Send completion signal with fallback info
                    echo "data: " . json_encode([
                        'complete' => true,
                        'is_fallback' => true,
                        'fallback_reason' => $fallbackReason,
                        'ai_provider' => 'fallback'
                    ]) . "\n\n";
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Streaming setup error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to start streaming chat.',
                'error_code' => 'STREAM_ERROR',
            ], 500);
        }
    }

    /**
     * Create the RillTech AI agent with RAG capabilities
     */
    private function createAgent(string $sessionId)
    {
        // Check if RAG services are available
        $hasVoyage = !empty(config('services.voyage.api_key'));
        $hasPinecone = !empty(config('services.pinecone.api_key'));
        $hasMistral = !empty(config('services.mistral.api_key'));

        Log::info('Creating RillTech AI Agent with RAG capabilities', [
            'session_id' => $sessionId,
            'voyage' => $hasVoyage,
            'pinecone' => $hasPinecone,
            'mistral' => $hasMistral,
            'rag_enabled' => $hasVoyage && $hasPinecone && $hasMistral
        ]);

        try {
            return RillTechAgent::makeWithSession($sessionId);
        } catch (\Exception $e) {
            Log::error('Failed to create RillTech agent', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle tool call execution when detected in AI response
     */
    private function handleToolCall(string $response, string $sessionId): string
    {
        try {
            // Try multiple approaches to extract tool call JSON
            $toolCall = null;
            $toolCallJson = '';

            // Approach 1: Look for array format [{"name": ...}]
            if (preg_match('/\[\s*\{[^}]*"name"[^}]*"arguments"[^}]*\}[^}]*\]/', $response, $matches)) {
                $toolCallJson = $matches[0];
                $decoded = json_decode($toolCallJson, true);
                if (is_array($decoded) && isset($decoded[0])) {
                    $toolCall = $decoded[0];
                }
            }

            // Approach 2: Look for single object format {"name": ...}
            if (!$toolCall && preg_match('/\{[^}]*"name"[^}]*"arguments"[^}]*\}/', $response, $matches)) {
                $toolCallJson = $matches[0];
                $toolCall = json_decode($toolCallJson, true);
            }

            // Approach 3: Try to find the complete JSON structure more carefully
            if (!$toolCall) {
                // Find the start of a JSON object that contains "name" and "arguments"
                $start = strpos($response, '{"name"');
                if ($start === false) {
                    $start = strpos($response, '[{"name"');
                }

                if ($start !== false) {
                    $jsonPart = substr($response, $start);
                    // Try to find the end by counting braces
                    $braceCount = 0;
                    $inString = false;
                    $escaped = false;
                    $end = 0;

                    for ($i = 0; $i < strlen($jsonPart); $i++) {
                        $char = $jsonPart[$i];

                        if ($escaped) {
                            $escaped = false;
                            continue;
                        }

                        if ($char === '\\') {
                            $escaped = true;
                            continue;
                        }

                        if ($char === '"') {
                            $inString = !$inString;
                            continue;
                        }

                        if (!$inString) {
                            if ($char === '{' || $char === '[') {
                                $braceCount++;
                            } elseif ($char === '}' || $char === ']') {
                                $braceCount--;
                                if ($braceCount === 0) {
                                    $end = $i + 1;
                                    break;
                                }
                            }
                        }
                    }

                    if ($end > 0) {
                        $toolCallJson = substr($jsonPart, 0, $end);
                        $decoded = json_decode($toolCallJson, true);
                        if (is_array($decoded) && isset($decoded[0])) {
                            $toolCall = $decoded[0];
                        } else {
                            $toolCall = $decoded;
                        }
                    }
                }
            }

            Log::info('Extracted tool call JSON', ['json' => $toolCallJson]);

            if ($toolCall && json_last_error() === JSON_ERROR_NONE && isset($toolCall['name'], $toolCall['arguments'])) {
                $toolName = $toolCall['name'];
                $arguments = $toolCall['arguments'];

                Log::info('Executing tool', ['tool' => $toolName, 'arguments' => $arguments]);

                // Execute the tool based on name
                if ($toolName === 'schedule_demo') {
                    $tool = new \App\AI\Tools\ScheduleDemo();

                    // Add session_id to arguments if not present or override with current session
                    $arguments['session_id'] = $sessionId;

                    // Normalize datetime format if needed
                    if (isset($arguments['preferred_datetime'])) {
                        $datetime = $arguments['preferred_datetime'];
                        // Convert ISO format to simple format if needed
                        if (str_contains($datetime, 'T')) {
                            $datetime = str_replace('T', ' ', $datetime);
                            $datetime = preg_replace('/:\d{2}$/', '', $datetime); // Remove seconds if present
                            $arguments['preferred_datetime'] = $datetime;
                        }
                    }

                    Log::info('Calling tool with normalized arguments', ['arguments' => $arguments]);

                    // Call the tool with arguments
                    $result = $tool(
                        $arguments['name'] ?? null,
                        $arguments['email'] ?? null,
                        $arguments['company'] ?? null,
                        $arguments['phone'] ?? null,
                        $arguments['demo_type'] ?? null,
                        $arguments['preferred_datetime'] ?? null,
                        $arguments['timezone'] ?? null,
                        $arguments['message'] ?? null,
                        $arguments['session_id'] ?? null
                    );

                    Log::info('Tool execution completed', ['result_length' => strlen($result)]);
                    return $result;
                } else {
                    Log::warning('Unknown tool name', ['tool' => $toolName]);
                    return "I'm sorry, I don't recognize that tool. Please try again or contact our support team.";
                }
            } else {
                Log::warning('Invalid tool call JSON structure');
            }

            // If tool call parsing fails, return original response
            Log::warning('Failed to parse tool call, returning original response');
            return $response;

        } catch (\Exception $e) {
            Log::error('Error executing tool call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'response' => $response,
                'session_id' => $sessionId
            ]);

            // Return a user-friendly error message
            return "I'd be happy to help you schedule a demo! However, I encountered an issue processing your request. " .
                   "Please try using the 'Schedule Demo' button on this page, or contact our team directly at hello@rilltech.com.";
        }
    }

    /**
     * Clear corrupted chat history for a session
     */
    private function clearChatHistory(string $sessionId): void
    {
        try {
            $historyPath = storage_path("app/chat_history/{$sessionId}.json");
            if (file_exists($historyPath)) {
                unlink($historyPath);
                Log::info('Cleared corrupted chat history', ['session_id' => $sessionId]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear chat history', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
