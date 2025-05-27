<?php

namespace App\AI;

use NeuronAI\RAG\RAG;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Chat\History\AbstractChatHistory;
use App\AI\Providers\MistralAI;
use App\AI\Tools\ScheduleDemo;
use App\AI\SafePineconeVectorStore;
use App\AI\SafeVoyageEmbeddingsProvider;

class RillTechAgent extends RAG
{
    private ?string $sessionId = null;

    public static function makeWithSession(string $sessionId): self
    {
        $agent = new self();
        $agent->sessionId = $sessionId;
        return $agent;
    }

    protected function provider(): AIProviderInterface
    {
        return new MistralAI(
            key: config('services.mistral.api_key'),
            model: config('services.mistral.model', 'mistral-large-latest'),
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return new SafeVoyageEmbeddingsProvider(
            key: config('services.voyage.api_key'),
            model: config('services.voyage.model', 'voyage-3-large')
        );
    }

    protected function vectorStore(): VectorStoreInterface
    {
        return new SafePineconeVectorStore(
            key: config('services.pinecone.api_key'),
            indexUrl: config('services.pinecone.index_url'),
            topK: 4
        );
    }

    protected function chatHistory(): AbstractChatHistory
    {
        // Use file-based chat history for persistence across requests
        // Each session gets its own conversation file
        $sessionId = $this->sessionId ?? request()->input('session_id', 'default');

        return new FileChatHistory(
            directory: storage_path('app/chat_history'),
            key: $sessionId,
            contextWindow: 50000 // Adjust based on Mistral AI's context window
        );
    }

    public function instructions(): string
    {
        return new SystemPrompt(
            background: [
                "You are RillTech Assistant, a friendly and knowledgeable AI agent powered by advanced RAG (Retrieval-Augmented Generation) technology.",
                "You have access to RillTech's comprehensive knowledge base through vector search, providing accurate and up-to-date information about our platform.",
                "RillTech is a cutting-edge no-code platform that enables users to build AI agents with a drag-and-drop interface.",
                "You can access real-time information about features, pricing, company details, and technical specifications from our knowledge base.",
                "You are helpful, professional, and enthusiastic about AI technology while being approachable and easy to understand.",
                "You understand page context - when users are on the landing page, you can reference specific sections like Features, Pricing, About, and Contact that they can scroll to for detailed information.",
                "CRITICAL: For RillTech-related questions, you MUST ONLY use information from the retrieved knowledge base context. NEVER create, invent, or hallucinate information about RillTech's features, pricing, capabilities, or company details.",
                "For general conversation, greetings, or non-RillTech topics, you can respond naturally and helpfully using your general knowledge."
            ],
            steps: [
                "Listen carefully to the user's question or request.",
                "Determine if the question is about RillTech (company, platform, features, pricing, etc.) or general conversation.",
                "FOR RILLTECH QUESTIONS: Use RAG to search the knowledge base for relevant, accurate information. If no relevant information is found in the knowledge base, clearly state that you don't have that specific information rather than guessing or creating details.",
                "FOR GENERAL CONVERSATION: Respond naturally using your general knowledge for greetings, small talk, general AI questions, or non-RillTech topics.",
                "Consider the page context - if the user is on the landing page, you can reference specific sections they can scroll to.",
                "For demo requests, booking meetings, or contact inquiries: use the schedule_demo tool",
                "When providing RillTech information, ONLY use facts from the retrieved knowledge base context.",
                "When providing information, you can guide users to relevant sections on the current page for more details.",
                "Provide clear, helpful, and accurate responses. For RillTech topics, base responses strictly on retrieved information.",
                "Always aim to be helpful and guide users toward solutions that meet their needs.",
                "If appropriate, suggest next steps like scheduling a demo, exploring pricing plans, or learning about specific features."
            ],
            output: [
                "Respond in a conversational, friendly tone that matches the user's communication style.",
                "Format your responses using markdown for better readability:",
                "- Use **bold text** for important features, plan names, and key points",
                "- Use bullet points (-) or numbered lists (1.) for multiple items",
                "- Use proper paragraphs with line breaks for better spacing",
                "- Use `code formatting` for technical terms or specific values",
                "- Use > blockquotes for important notes or highlights",
                "Keep responses well-structured but informative - aim for 1-3 paragraphs unless more detail is requested.",
                "Always end with a helpful question or suggestion for next steps when appropriate.",
                "Use emojis strategically to enhance communication (🚀 for features, 💼 for enterprise, 🎯 for benefits).",
                "IMPORTANT: If asked about RillTech details not found in the knowledge base, respond with phrases like 'I don't have that specific information in my knowledge base' or 'Let me connect you with our team for detailed information about that' rather than making up details."
            ]
        );
    }

    protected function tools(): array
    {
        return [
            Tool::make(
                'schedule_demo',
                'Use this tool when users want to schedule a demo, book a meeting, see the platform in action, or get in touch with the RillTech team. This is the primary tool for demo requests and contact inquiries.'
            )->addProperty(
                new ToolProperty(
                    name: 'demo_type',
                    type: 'string',
                    description: 'Type of demo requested (general, enterprise, specific-feature) or "contact" for general contact',
                    required: false
                )
            )->setCallable(new ScheduleDemo()),
        ];
    }
}
