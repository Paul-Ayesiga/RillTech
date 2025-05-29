<?php

namespace App\AI\Tools;

use App\Models\DemoRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ScheduleDemo
{
    /**
     * Schedule a demo with the provided information
     *
     * @param string|null $name Full name of the person requesting the demo
     * @param string|null $email Email address for demo confirmation
     * @param string|null $company Company name (optional)
     * @param string|null $phone Phone number (optional)
     * @param string|null $demo_type Type of demo (general|enterprise|specific-feature|custom)
     * @param string|null $preferred_datetime Preferred date and time (e.g., "2024-01-15 14:00")
     * @param string|null $timezone Timezone (e.g., "America/New_York")
     * @param string|null $message Additional message or requirements
     * @param string|null $session_id Chat session ID for tracking
     * @return string Response message about the demo scheduling
     */
    public function __invoke(
        ?string $name = null,
        ?string $email = null,
        ?string $company = null,
        ?string $phone = null,
        ?string $demo_type = null,
        ?string $preferred_datetime = null,
        ?string $timezone = null,
        ?string $message = null,
        ?string $session_id = null
    ): string {
        // If no parameters provided, show demo options
        if (!$name && !$email && !$demo_type && !$preferred_datetime) {
            return $this->showDemoOptions();
        }

        // Validate required information
        $missingInfo = $this->validateRequiredInfo($name, $email, $demo_type, $preferred_datetime, $timezone);
        if (!empty($missingInfo)) {
            return $this->requestMissingInfo($missingInfo);
        }

        try {
            // Check availability first
            $availabilityResult = $this->checkAvailability($preferred_datetime, $timezone);
            if (!$availabilityResult['available']) {
                return $this->handleUnavailableTime($availabilityResult['suggested_times']);
            }

            // Create the demo request
            $demoRequest = $this->createDemoRequest([
                'name' => $name,
                'email' => $email,
                'company' => $company,
                'phone' => $phone,
                'demo_type' => $demo_type,
                'preferred_datetime' => $preferred_datetime,
                'timezone' => $timezone ?? 'Africa/Kampala',
                'message' => $message,
                'session_id' => $session_id ?? 'chat-' . uniqid(),
            ]);

            if ($demoRequest) {
                return $this->formatSuccessResponse($demoRequest);
            } else {
                return $this->formatErrorResponse();
            }

        } catch (Exception $e) {
            Log::error('Error in ScheduleDemo tool', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => compact('name', 'email', 'company', 'phone', 'demo_type', 'preferred_datetime', 'timezone', 'message', 'session_id')
            ]);

            return $this->formatErrorResponse($e->getMessage());
        }
    }

    /**
     * Validate that all required information is provided
     */
    private function validateRequiredInfo(?string $name, ?string $email, ?string $demo_type, ?string $preferred_datetime, ?string $timezone): array
    {
        $missing = [];

        if (!$name) {
            $missing[] = 'name';
        }

        if (!$email) {
            $missing[] = 'email';
        }

        if (!$demo_type) {
            $missing[] = 'demo_type';
        }

        if (!$preferred_datetime) {
            $missing[] = 'preferred_datetime';
        }

        if (!$timezone) {
            $missing[] = 'timezone';
        }

        // Validate email format if provided
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $missing[] = 'valid_email';
        }

        // Validate demo type if provided
        if ($demo_type && !in_array($demo_type, ['general', 'enterprise', 'specific-feature', 'custom'])) {
            $missing[] = 'valid_demo_type';
        }

        // Validate datetime format if provided
        if ($preferred_datetime) {
            try {
                $datetime = Carbon::parse($preferred_datetime);
                if ($datetime->isPast()) {
                    $missing[] = 'future_datetime';
                }
            } catch (Exception) {
                $missing[] = 'valid_datetime';
            }
        }

        return $missing;
    }

    /**
     * Request missing information from the user
     */
    private function requestMissingInfo(array $missing): string
    {
        $messages = [
            'name' => 'your full name',
            'email' => 'your email address',
            'demo_type' => 'the type of demo you\'d like (general, enterprise, specific-feature, or custom)',
            'preferred_datetime' => 'your preferred date and time (e.g., "January 15, 2024 at 2:00 PM")',
            'timezone' => 'your timezone (e.g., "America/New_York" or "Eastern Time")',
            'valid_email' => 'a valid email address',
            'valid_demo_type' => 'a valid demo type (general, enterprise, specific-feature, or custom)',
            'valid_datetime' => 'a valid date and time format',
            'future_datetime' => 'a future date and time (not in the past)'
        ];

        $missingMessages = array_map(fn($key) => $messages[$key] ?? $key, $missing);

        if (count($missingMessages) === 1) {
            return "I need " . $missingMessages[0] . " to schedule your demo. Could you please provide that?";
        }

        $lastItem = array_pop($missingMessages);
        return "I need the following information to schedule your demo:\n\n• " .
               implode("\n• ", $missingMessages) .
               "\n• " . $lastItem .
               "\n\nCould you please provide these details?";
    }

    /**
     * Check if the requested time slot is available
     */
    private function checkAvailability(string $preferred_datetime, string $timezone): array
    {
        try {
            $requestedTime = Carbon::parse($preferred_datetime)->setTimezone($timezone);

            // Convert to UTC for database comparison
            $utcTime = $requestedTime->utc();

            // Check if there's already a confirmed demo within 1 hour of the requested time
            $conflictingDemo = DemoRequest::where('status', 'confirmed')
                ->whereBetween('confirmed_datetime', [
                    $utcTime->copy()->subHour(),
                    $utcTime->copy()->addHour()
                ])
                ->exists();

            if ($conflictingDemo) {
                // Generate suggested alternative times
                $suggestedTimes = $this->getSuggestedTimes($requestedTime, $timezone);

                return [
                    'available' => false,
                    'suggested_times' => $suggestedTimes
                ];
            }

            return ['available' => true];

        } catch (Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'datetime' => $preferred_datetime,
                'timezone' => $timezone
            ]);

            return ['available' => false, 'suggested_times' => []];
        }
    }

    /**
     * Get suggested alternative times when requested time is unavailable
     */
    private function getSuggestedTimes(Carbon $requestedTime, string $timezone): array
    {
        $suggestions = [];
        $baseTime = $requestedTime->copy();

        // Suggest 3 alternative times: +2 hours, +1 day same time, +1 day +2 hours
        $alternatives = [
            $baseTime->copy()->addHours(2),
            $baseTime->copy()->addDay(),
            $baseTime->copy()->addDay()->addHours(2),
        ];

        foreach ($alternatives as $alternative) {
            // Skip if it's outside business hours (9 AM - 6 PM)
            if ($alternative->hour < 9 || $alternative->hour > 18) {
                continue;
            }

            // Skip weekends
            if ($alternative->isWeekend()) {
                continue;
            }

            $utcAlternative = $alternative->utc();

            // Check if this time is available
            $isAvailable = !DemoRequest::where('status', 'confirmed')
                ->whereBetween('confirmed_datetime', [
                    $utcAlternative->copy()->subHour(),
                    $utcAlternative->copy()->addHour()
                ])
                ->exists();

            if ($isAvailable) {
                $suggestions[] = [
                    'datetime' => $alternative->toISOString(),
                    'formatted' => $alternative->format('M j, Y \a\t g:i A T'),
                    'timezone' => $timezone
                ];
            }

            // Limit to 3 suggestions
            if (count($suggestions) >= 3) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Handle unavailable time slot by suggesting alternatives
     */
    private function handleUnavailableTime(array $suggestedTimes): string
    {
        if (empty($suggestedTimes)) {
            return "I'm sorry, but the requested time slot is not available and I couldn't find any suitable alternatives. " .
                   "Please try a different time during business hours (9 AM - 6 PM, Monday-Friday) or contact our team directly for assistance.";
        }

        $response = "I'm sorry, but the requested time slot is not available. However, I found these alternative times that work:\n\n";

        foreach ($suggestedTimes as $index => $suggestion) {
            $response .= "**Option " . ($index + 1) . ":** " . $suggestion['formatted'] . "\n";
        }

        $response .= "\nWould you like me to schedule the demo for one of these alternative times? Just let me know which option works best for you!";

        return $response;
    }

    /**
     * Create a demo request in the database
     */
    private function createDemoRequest(array $data): ?DemoRequest
    {
        try {
            // Parse and format the datetime
            $datetime = Carbon::parse($data['preferred_datetime'])->setTimezone($data['timezone']);

            $demoRequest = DemoRequest::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'company' => $data['company'],
                'phone' => $data['phone'],
                'demo_type' => $data['demo_type'],
                'preferred_datetime' => $datetime->utc(),
                'timezone' => $data['timezone'],
                'message' => $data['message'],
                'session_id' => $data['session_id'],
                'source' => 'chatbot',
                'status' => 'pending',
                'metadata' => [
                    'scheduled_via' => 'ai_agent',
                    'original_timezone' => $data['timezone'],
                    'formatted_time' => $datetime->format('M j, Y \a\t g:i A T')
                ]
            ]);

            Log::info('Demo request created via AI agent', [
                'demo_request_id' => $demoRequest->id,
                'session_id' => $data['session_id'],
                'email' => $data['email'],
                'preferred_datetime' => $data['preferred_datetime']
            ]);

            return $demoRequest;

        } catch (Exception $e) {
            Log::error('Error creating demo request', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return null;
        }
    }

    /**
     * Format success response when demo is scheduled
     */
    private function formatSuccessResponse(DemoRequest $demoRequest): string
    {
        $demoTypes = [
            'general' => 'General Platform Demo',
            'enterprise' => 'Enterprise Demo',
            'specific-feature' => 'Feature-Focused Demo',
            'custom' => 'Custom Demo'
        ];

        $demoTypeName = $demoTypes[$demoRequest->demo_type] ?? 'Demo';
        $formattedTime = $demoRequest->formatted_preferred_datetime;

        return "🎉 **Demo Successfully Scheduled!**\n\n" .
               "Great news! I've successfully scheduled your **{$demoTypeName}** for:\n\n" .
               "📅 **Date & Time:** {$formattedTime}\n" .
               "👤 **Name:** {$demoRequest->name}\n" .
               "📧 **Email:** {$demoRequest->email}\n" .
               ($demoRequest->company ? "🏢 **Company:** {$demoRequest->company}\n" : "") .
               ($demoRequest->phone ? "📞 **Phone:** {$demoRequest->phone}\n" : "") .
               "\n**What happens next?**\n" .
               "• You'll receive a confirmation email shortly\n" .
               "• Our team will send you calendar invite with meeting details\n" .
               "• We'll include preparation materials and agenda\n" .
               "• If you need to reschedule, just reply to the confirmation email\n\n" .
               "**Demo ID:** #{$demoRequest->id}\n\n" .
               "Looking forward to showing you what RillTech can do for your business! 🚀\n\n" .
               "Is there anything specific you'd like us to focus on during the demo?";
    }

    /**
     * Format error response when something goes wrong
     */
    private function formatErrorResponse(?string $error = null): string
    {
        $baseMessage = "I apologize, but I encountered an issue while trying to schedule your demo. ";

        if ($error) {
            $baseMessage .= "Error details: {$error}\n\n";
        }

        return $baseMessage .
               "**Don't worry - here are your options:**\n\n" .
               "1. **Try again** - I can attempt to schedule the demo again with the same or different details\n" .
               "2. **Manual booking** - Use the 'Schedule Demo' button on this page for manual booking\n" .
               "3. **Contact us directly** - Email us at sales@rilltech.com or hello@rilltech.com\n\n" .
               "Would you like me to try scheduling the demo again, or would you prefer one of the other options?";
    }

    /**
     * Show available demo options when no specific request is made
     */
    private function showDemoOptions(): string
    {
        return "I'd be happy to help you schedule a demo! We offer several types of demos:\n\n" .
               "🎯 **General Platform Demo** (30 minutes)\n" .
               "Perfect for getting an overview of RillTech's capabilities and seeing how our AI agents work.\n\n" .
               "🏢 **Enterprise Demo** (45 minutes)\n" .
               "Deep dive into enterprise features, security, compliance, and custom solutions.\n\n" .
               "⚡ **Feature-Focused Demo** (20 minutes)\n" .
               "Focused on specific features or use cases you're most interested in.\n\n" .
               "🎨 **Custom Demo** (flexible duration)\n" .
               "Tailored demonstration based on your unique requirements.\n\n" .
               "**To schedule a demo, I'll need:**\n" .
               "• Your name and email\n" .
               "• Which type of demo you'd prefer\n" .
               "• Your preferred date and time\n" .
               "• Your timezone\n\n" .
               "Just provide these details and I'll schedule it for you right away! 🚀\n\n" .
               "Which type of demo interests you most?";
    }
}
