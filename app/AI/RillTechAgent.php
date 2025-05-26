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
use App\AI\Tools\GetCompanyInfo;

class RillTechAgent extends Agent
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
                "You are RillTech Assistant, a friendly and knowledgeable AI agent for RillTech - a cutting-edge platform that enables users to build AI agents with a no-code drag-and-drop interface.",
                "RillTech helps businesses and individuals create powerful AI assistants in minutes, not months.",
                "You have deep knowledge about AI agents, no-code development, automation, and RillTech's specific features and pricing.",
                "You are helpful, professional, and enthusiastic about AI technology while being approachable and easy to understand.",
                "You understand page context - when users are on the landing page, you can reference specific sections like Features, Pricing, About, and Contact that they can scroll to for detailed information."
            ],
            steps: [
                "Listen carefully to the user's question or request.",
                "Consider the page context - if the user is on the landing page, you can reference specific sections they can scroll to.",
                "Use the appropriate tools based on user intent:",
                "- For demo requests, booking meetings, or contact inquiries: use schedule_demo tool",
                "- For pricing questions: use get_pricing_info tool",
                "- For feature questions: use get_feature_info tool",
                "- For company information: use get_company_info tool",
                "When providing information, you can guide users to relevant sections on the current page for more details.",
                "Provide clear, helpful, and accurate responses based on the information available.",
                "If you don't have specific information, be honest about it and offer to help in other ways.",
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
                "Use emojis strategically to enhance communication (🚀 for features, 💼 for enterprise, 🎯 for benefits)."
            ]
        );
    }

    protected function tools(): array
    {
        return [
            Tool::make(
                'get_pricing_info',
                'Get detailed information about RillTech pricing plans, features included in each plan, and pricing comparisons.'
            )->addProperty(
                new ToolProperty(
                    name: 'plan_type',
                    type: 'string',
                    description: 'Specific plan to get info about (starter, professional, enterprise) or "all" for all plans',
                    required: false
                )
            )->setCallable(new GetPricingInfo()),

            Tool::make(
                'get_feature_info',
                'Get detailed information about RillTech features, capabilities, and how they work.'
            )->addProperty(
                new ToolProperty(
                    name: 'feature_category',
                    type: 'string',
                    description: 'Category of features to get info about (drag-drop, ai-models, integrations, automation, analytics, etc.) or specific feature name',
                    required: false
                )
            )->setCallable(new GetFeatureInfo()),

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

            Tool::make(
                'get_company_info',
                'Get information about RillTech as a company, team, mission, and general platform overview.'
            )->addProperty(
                new ToolProperty(
                    name: 'info_type',
                    type: 'string',
                    description: 'Type of company info requested (about, team, mission, technology, security, etc.)',
                    required: false
                )
            )->setCallable(new GetCompanyInfo()),
        ];
    }
}
