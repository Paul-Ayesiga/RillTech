<?php

namespace App\AI\Tools;

class ScheduleDemo
{
    public function __invoke(?string $demo_type = null, ?string $company_size = null, ?string $use_case = null): string
    {
        $demoOptions = [
            'general' => [
                'name' => 'General Platform Demo',
                'duration' => '30 minutes',
                'description' => 'Get a comprehensive overview of RillTech\'s capabilities',
                'includes' => [
                    'Platform overview and key features',
                    'Live demonstration of the drag & drop builder',
                    'Sample AI agent creation walkthrough',
                    'Q&A session tailored to your needs',
                    'Pricing discussion and plan recommendations'
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise Demo',
                'duration' => '45 minutes',
                'description' => 'Deep dive into enterprise features and custom solutions',
                'includes' => [
                    'Enterprise security and compliance overview',
                    'Custom integration possibilities',
                    'Scalability and performance discussion',
                    'White-label and on-premise options',
                    'Custom pricing and implementation timeline',
                    'Technical architecture review'
                ]
            ],
            'specific-feature' => [
                'name' => 'Feature-Focused Demo',
                'duration' => '20 minutes',
                'description' => 'Focused demonstration of specific features you\'re interested in',
                'includes' => [
                    'Deep dive into requested features',
                    'Use case examples relevant to your industry',
                    'Integration demonstrations',
                    'Best practices and tips',
                    'Implementation guidance'
                ]
            ]
        ];

        // For any specific use case, provide personalized demo response
        if ($use_case) {
            return $this->formatPersonalizedDemo($company_size, $use_case);
        }

        if ($demo_type && $demo_type !== 'contact' && isset($demoOptions[strtolower($demo_type)])) {
            return $this->formatDemoOption($demoOptions[strtolower($demo_type)], $company_size, $use_case);
        }

        if ($demo_type === 'contact') {
            return $this->formatContactInfo();
        }

        return $this->formatAllDemoOptions($demoOptions);
    }

    private function formatDemoOption(array $demo, ?string $company_size = null, ?string $use_case = null): string
    {
        $includes = implode("\n• ", $demo['includes']);

        return "**{$demo['name']} ({$demo['duration']})**\n\n" .
               "{$demo['description']}\n\n" .
               "**What's included:**\n• {$includes}\n\n" .
               "**Ready to schedule?**\n" .
               "Please scroll down to the **Contact** section on this page and reach out to us with the subject line **\"Demo Request\"**. \n\n" .
               "In your message, please mention:\n" .
               "• **Demo Type:** {$demo['name']}\n" .
               "• **Company Size:** " . ($company_size ?: 'Your company size') . "\n" .
               "• **Use Case:** " . ($use_case ?: 'Your specific use case') . "\n" .
               "• **Preferred Time:** Any timing preferences\n\n" .
               "Our team will get back to you within 24 hours to schedule your personalized demo!";
    }

    private function formatPersonalizedDemo(?string $company_size = null, ?string $use_case = null): string
    {
        $companyText = $company_size ? ucfirst($company_size) : 'Your';
        $useCaseText = $use_case ? str_replace('_', ' ', ucwords(str_replace('_', ' ', $use_case))) : 'your specific needs';

        return "**Perfect! Let's schedule a demo tailored to your needs! 🚀**\n\n" .
               "Based on your requirements for **{$useCaseText}**, I recommend our **General Platform Demo** which will cover:\n\n" .
               "• **Platform overview** and key features\n" .
               "• **Use case-specific** demonstrations\n" .
               "• **Integration capabilities** relevant to your needs\n" .
               "• **AI agent creation** walkthrough\n" .
               "• **Pricing options** suitable for {$companyText} companies\n" .
               "• **Q&A session** tailored to your requirements\n\n" .
               "**Ready to schedule?**\n" .
               "Please scroll down to the **Contact** section on this page and reach out to us with the subject line **\"Demo Request\"**.\n\n" .
               "In your message, please mention:\n" .
               "• **Demo Type:** General Platform Demo\n" .
               "• **Company Size:** {$companyText}\n" .
               "• **Use Case:** {$useCaseText}\n" .
               "• **Preferred Time:** Any timing preferences\n\n" .
               "**Alternative:** You can also email us directly at **hello@rilltech.com** or **sales@rilltech.com**\n\n" .
               "Our team will get back to you within 24 hours to schedule your personalized demo!";
    }

    private function formatAllDemoOptions(array $demoOptions): string
    {
        $output = "I'd be happy to help you schedule a demo! We offer several demo options:\n\n";

        foreach ($demoOptions as $demo) {
            $output .= "**{$demo['name']} ({$demo['duration']})**\n";
            $output .= "{$demo['description']}\n\n";
        }

        $output .= "**How to schedule:**\n";
        $output .= "Please scroll down to the **Contact** section on this page and reach out to us with the subject line **\"Demo Request\"**.\n\n";
        $output .= "In your message, please mention:\n";
        $output .= "• **Demo Type:** Which demo you're interested in\n";
        $output .= "• **Company Size:** Your company size\n";
        $output .= "• **Preferred Time:** Any timing preferences\n\n";

        $output .= "Our team will get back to you within 24 hours to schedule your personalized demo!\n\n";
        $output .= "Which type of demo interests you most?";

        return $output;
    }

    private function formatContactInfo(): string
    {
        return "**Get in Touch with RillTech**\n\n" .
               "I'd be happy to help you connect with our team!\n\n" .
               "**For Demo Requests:**\n" .
               "Please scroll down to the **Contact** section on this page and reach out to us with the subject line **\"Demo Request\"**.\n\n" .
               "**For General Inquiries:**\n" .
               "You can also reach us directly at:\n" .
               "• **General inquiries:** hello@rilltech.com\n" .
               "• **Sales team:** sales@rilltech.com\n" .
               "• **Support:** support@rilltech.com\n\n" .
               "**Response Time:**\n" .
               "• We respond within 24 hours\n" .
               "• Available Monday-Friday, 9 AM - 6 PM EST\n\n" .
               "Would you like me to help you with anything specific before you reach out?";
    }
}
