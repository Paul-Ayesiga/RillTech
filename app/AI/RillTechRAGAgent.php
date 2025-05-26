<?php

namespace App\AI;

use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Chat\History\AbstractChatHistory;
use App\AI\Providers\MistralAI;
use App\AI\Tools\GetPricingInfo;
use App\AI\Tools\GetFeatureInfo;
use App\AI\Tools\ScheduleDemo;
use App\AI\Tools\GetCompanyInfoRAG;

class RillTechRAGAgent extends Agent
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

    // TODO: Add RAG functionality when Neuron AI RAG packages are available
    // For now, this agent uses enhanced tools with intelligent knowledge matching

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
                "You are the RillTech AI Assistant, an expert on RillTech's AI agent platform.",
                "RillTech is a cutting-edge no-code platform that enables businesses to create AI agents through drag-and-drop interfaces.",
                "You have access to real-time pricing information from Stripe and comprehensive company knowledge through RAG.",
                "You can help users understand our platform, pricing, features, and schedule demos.",
                "You understand page context - when users are on the landing page, you can reference specific sections like Features, Pricing, About, and Contact that they can scroll to for detailed information."
            ],
            steps: [
                "Always be helpful, professional, and enthusiastic about RillTech's capabilities.",
                "Consider the page context - if the user is on the landing page, you can reference specific sections they can scroll to.",
                "Use the appropriate tools based on user intent:",
                "- For demo requests, booking meetings, or contact inquiries: use schedule_demo tool",
                "- For pricing questions: use get_pricing_info tool to fetch real Stripe data",
                "- For feature questions: use get_feature_info tool",
                "- For company information: use get_company_info tool with RAG system",
                "When providing information, you can guide users to relevant sections on the current page for more details.",
                "Always offer to help with next steps or additional questions.",
                "Be conversational and engaging while maintaining professionalism.",
                "If you don't have specific information, be honest and offer to connect them with our team."
            ],
            output: [
                "Format your responses using markdown for excellent readability:",
                "- Use **bold text** for important features, plan names, and key points",
                "- Use bullet points (-) or numbered lists (1.) for multiple items",
                "- Use proper paragraphs with line breaks for better spacing",
                "- Use `code formatting` for technical terms or specific values",
                "- Use > blockquotes for important notes or highlights",
                "Never make up pricing information - always use the pricing tool.",
                "Don't promise features that aren't confirmed - stick to documented capabilities.",
                "Always prioritize user success and satisfaction.",
                "Use emojis strategically to enhance communication (🚀 for features, 💼 for enterprise, 🎯 for benefits).",
                "If technical questions are beyond your scope, offer to connect with technical support."
            ]
        );
    }

    protected function tools(): array
    {
        return [
            Tool::make(
                'get_pricing_info',
                'Get real-time pricing information from Stripe for RillTech plans and features'
            )->addProperty(
                new ToolProperty(
                    name: 'plan_type',
                    type: 'string',
                    description: 'The type of plan to get pricing for (starter, professional, enterprise, or all)',
                    required: false
                )
            )->setCallable(function (?string $plan_type = null) {
                $tool = new GetPricingInfo();
                return $tool($plan_type);
            }),

            Tool::make(
                'get_company_info',
                'Get comprehensive company information using RAG from our knowledge base'
            )->addProperty(
                new ToolProperty(
                    name: 'query',
                    type: 'string',
                    description: 'Specific question or topic about the company (mission, technology, team, security, etc.)',
                    required: false
                )
            )->setCallable(function (?string $query = null) {
                $tool = new GetCompanyInfoRAG();
                return $tool($query);
            }),

            Tool::make(
                'get_feature_info',
                'Get detailed information about RillTech platform features and capabilities'
            )->addProperty(
                new ToolProperty(
                    name: 'feature_category',
                    type: 'string',
                    description: 'Category of features to explore (builder, integrations, ai-models, analytics, security, automation)',
                    required: false
                )
            )->setCallable(function (?string $feature_category = null) {
                $tool = new GetFeatureInfo();
                return $tool($feature_category);
            }),

            Tool::make(
                'schedule_demo',
                'Help users schedule a demo of the RillTech platform'
            )->addProperty(
                new ToolProperty(
                    name: 'demo_type',
                    type: 'string',
                    description: 'Type of demo requested (general, enterprise, feature-specific, technical)',
                    required: false
                )
            )->addProperty(
                new ToolProperty(
                    name: 'company_size',
                    type: 'string',
                    description: 'Size of the user\'s company (startup, small, medium, large, enterprise)',
                    required: false
                )
            )->addProperty(
                new ToolProperty(
                    name: 'use_case',
                    type: 'string',
                    description: 'Primary use case or industry focus',
                    required: false
                )
            )->setCallable(function (?string $demo_type = null, ?string $company_size = null, ?string $use_case = null) {
                $tool = new ScheduleDemo();
                return $tool($demo_type, $company_size, $use_case);
            })
        ];
    }
}
