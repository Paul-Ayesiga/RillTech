<?php

namespace App\AI\Tools;

class GetFeatureInfo
{
    public function __invoke(?string $feature_category = null): string
    {
        $features = [
            'drag-drop' => [
                'name' => 'Drag & Drop Builder',
                'description' => 'Our intuitive visual builder makes creating AI agents as easy as building with blocks',
                'details' => [
                    'Visual workflow designer with pre-built components',
                    'No coding required - everything is point-and-click',
                    'Real-time preview of your agent as you build',
                    'Template library with ready-made agent blueprints',
                    'Conditional logic and branching conversations',
                    'Easy integration setup with visual connectors'
                ]
            ],
            'ai-models' => [
                'name' => 'AI Models & Intelligence',
                'description' => 'Powered by the latest AI models for natural, intelligent conversations',
                'details' => [
                    'GPT-4 and Claude integration for advanced reasoning',
                    'Multiple model options for different use cases',
                    'Custom training on your specific data',
                    'Natural language understanding and generation',
                    'Context awareness across conversations',
                    'Multilingual support (50+ languages)'
                ]
            ],
            'integrations' => [
                'name' => 'Integrations & Connectivity',
                'description' => 'Connect your AI agents to the tools and platforms you already use',
                'details' => [
                    'CRM integrations (Salesforce, HubSpot, Pipedrive)',
                    'Communication platforms (Slack, Teams, Discord)',
                    'E-commerce platforms (Shopify, WooCommerce)',
                    'Help desk systems (Zendesk, Intercom)',
                    'Database connections (MySQL, PostgreSQL, MongoDB)',
                    'REST API and webhook support',
                    'Zapier integration for 5000+ apps'
                ]
            ],
            'automation' => [
                'name' => 'Automation & Workflows',
                'description' => 'Automate repetitive tasks and create intelligent workflows',
                'details' => [
                    'Trigger-based automation (time, events, conditions)',
                    'Multi-step workflows with decision points',
                    'Data processing and transformation',
                    'Scheduled tasks and recurring actions',
                    'Error handling and retry mechanisms',
                    'Approval workflows and human-in-the-loop processes'
                ]
            ],
            'analytics' => [
                'name' => 'Analytics & Insights',
                'description' => 'Understand how your AI agents are performing and improving',
                'details' => [
                    'Real-time conversation analytics',
                    'User satisfaction and feedback tracking',
                    'Performance metrics and KPI dashboards',
                    'A/B testing for agent improvements',
                    'Custom reporting and data exports',
                    'Conversation flow analysis and optimization suggestions'
                ]
            ],
            'security' => [
                'name' => 'Security & Compliance',
                'description' => 'Enterprise-grade security to protect your data and conversations',
                'details' => [
                    'End-to-end encryption for all data',
                    'SOC 2 Type II compliance',
                    'GDPR and CCPA compliance',
                    'Role-based access controls',
                    'Audit logs and activity monitoring',
                    'Data residency options',
                    'Single Sign-On (SSO) support'
                ]
            ]
        ];

        if ($feature_category && isset($features[strtolower($feature_category)])) {
            return $this->formatSingleFeature($features[strtolower($feature_category)]);
        }

        // If no specific category or category not found, return overview
        return $this->formatFeatureOverview($features);
    }

    private function formatSingleFeature(array $feature): string
    {
        $details = implode("\n• ", $feature['details']);
        
        return "**{$feature['name']}**\n\n" .
               "{$feature['description']}\n\n" .
               "**Key capabilities:**\n• {$details}\n\n" .
               "Would you like to know more about how this feature works or see it in action with a demo?";
    }

    private function formatFeatureOverview(array $features): string
    {
        $output = "Here's an overview of RillTech's key features:\n\n";
        
        foreach ($features as $key => $feature) {
            $output .= "**{$feature['name']}**\n";
            $output .= "{$feature['description']}\n\n";
        }
        
        $output .= "Each feature is designed to work seamlessly together, creating a powerful platform for building intelligent AI agents.\n\n";
        $output .= "Would you like detailed information about any specific feature, or would you prefer to see how they work together in a demo?";
        
        return $output;
    }
}
