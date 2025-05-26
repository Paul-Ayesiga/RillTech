<?php

namespace App\AI\Tools;

class GetCompanyInfo
{
    public function __invoke(?string $info_type = null): string
    {
        $companyInfo = [
            'about' => [
                'title' => 'About RillTech',
                'content' => "RillTech is a cutting-edge platform that democratizes AI agent creation through our intuitive no-code drag-and-drop interface. Founded with the vision of making AI accessible to everyone, we enable businesses and individuals to build powerful AI assistants in minutes, not months.\n\nOur platform bridges the gap between complex AI technology and practical business solutions, empowering users to automate workflows, enhance customer experiences, and drive innovation without requiring technical expertise."
            ],
            'mission' => [
                'title' => 'Our Mission',
                'content' => "To democratize AI technology by making it accessible, intuitive, and powerful for everyone. We believe that every business, regardless of size or technical expertise, should be able to harness the power of AI to solve problems, automate processes, and create exceptional experiences.\n\nWe're committed to breaking down the barriers between humans and AI, creating tools that amplify human creativity and productivity rather than replacing it."
            ],
            'technology' => [
                'title' => 'Technology & Innovation',
                'content' => "RillTech is built on a foundation of cutting-edge AI technologies:\n\n• **Advanced AI Models**: Integration with GPT-4, Claude, and other leading language models\n• **Scalable Architecture**: Cloud-native infrastructure that scales with your needs\n• **Visual Development**: Proprietary drag-and-drop interface that makes AI development intuitive\n• **Smart Integrations**: Seamless connectivity with 1000+ business tools and platforms\n• **Real-time Processing**: Low-latency responses for immediate user interactions\n• **Continuous Learning**: AI agents that improve over time through interaction data"
            ],
            'security' => [
                'title' => 'Security & Compliance',
                'content' => "Security is at the core of everything we do at RillTech:\n\n• **Enterprise-Grade Security**: End-to-end encryption, secure data centers, and regular security audits\n• **Compliance Standards**: SOC 2 Type II, GDPR, CCPA, and HIPAA compliance\n• **Data Privacy**: Your data remains yours - we never use customer data to train our models\n• **Access Controls**: Role-based permissions and single sign-on (SSO) support\n• **Monitoring**: 24/7 security monitoring and incident response\n• **Certifications**: ISO 27001 and other industry-standard certifications"
            ],
            'team' => [
                'title' => 'Our Team',
                'content' => "RillTech is powered by a diverse team of AI researchers, software engineers, product designers, and business strategists who share a passion for making AI accessible to everyone.\n\nOur leadership team brings together decades of experience from leading technology companies, AI research institutions, and successful startups. We're united by our commitment to innovation, customer success, and building technology that makes a positive impact.\n\nWe're always looking for talented individuals who want to shape the future of AI. Check out our careers page to see current opportunities!"
            ],
            'values' => [
                'title' => 'Our Values',
                'content' => "**Innovation**: We push the boundaries of what's possible with AI technology\n**Accessibility**: We make complex technology simple and accessible to everyone\n**Reliability**: We build robust, dependable solutions our customers can trust\n**Transparency**: We believe in open communication and honest relationships\n**Customer Success**: Your success is our success - we're committed to helping you achieve your goals\n**Continuous Learning**: We embrace change and continuously improve our platform and ourselves"
            ]
        ];

        if ($info_type && isset($companyInfo[strtolower($info_type)])) {
            $info = $companyInfo[strtolower($info_type)];
            return "**{$info['title']}**\n\n{$info['content']}\n\nWould you like to know more about any other aspect of RillTech?";
        }

        return $this->formatCompanyOverview($companyInfo);
    }

    private function formatCompanyOverview(array $companyInfo): string
    {
        $output = "**Welcome to RillTech!** 🚀\n\n";
        $output .= "We're a cutting-edge platform that makes AI agent creation accessible to everyone through our intuitive no-code drag-and-drop interface.\n\n";
        
        $output .= "**Quick Overview:**\n";
        $output .= "• **Founded**: To democratize AI technology for businesses of all sizes\n";
        $output .= "• **Platform**: No-code AI agent builder with drag-and-drop interface\n";
        $output .= "• **Mission**: Make AI accessible, intuitive, and powerful for everyone\n";
        $output .= "• **Technology**: Built on advanced AI models with enterprise-grade security\n";
        $output .= "• **Customers**: Serving businesses from startups to Fortune 500 companies\n\n";
        
        $output .= "**Learn More About:**\n";
        $output .= "• Our mission and values\n";
        $output .= "• Technology and innovation\n";
        $output .= "• Security and compliance\n";
        $output .= "• Our team and culture\n\n";
        
        $output .= "What would you like to know more about, or would you prefer to see our platform in action with a demo?";
        
        return $output;
    }
}
