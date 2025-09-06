<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DemoRequestController extends Controller
{
    /**
     * Store a new demo request (for manual booking)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'message' => 'nullable|string|max:1000',
                'demo_type' => 'required|in:general,enterprise,specific-feature,custom',
                'preferred_datetime' => 'required|date|after:now',
                'timezone' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Check if the requested time slot is available
            $availability = $this->checkAvailability($validated['preferred_datetime'], $validated['timezone']);

            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested time slot is not available.',
                    'suggested_times' => $availability['suggested_times']
                ], 409);
            }

            // Create the demo request
            $demoRequest = DemoRequest::create([
                ...$validated,
                'user_id' => Auth::id(),
                'source' => 'manual',
                'status' => 'pending',
            ]);

            Log::info('Demo request created manually', [
                'demo_request_id' => $demoRequest->id,
                'user_id' => Auth::id(),
                'email' => $validated['email'],
                'preferred_datetime' => $validated['preferred_datetime']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demo request submitted successfully! We will contact you soon to confirm the details.',
                'demo_request' => $demoRequest->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating demo request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your demo request. Please try again.'
            ], 500);
        }
    }

    /**
     * Store a demo request via chatbot
     */
    public function storeViaChatbot(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'message' => 'nullable|string|max:1000',
                'demo_type' => 'required|in:general,enterprise,specific-feature,custom',
                'preferred_datetime' => 'required|date|after:now',
                'timezone' => 'required|string|max:50',
                'session_id' => 'required|string|max:100',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid information provided',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Check availability
            $availability = $this->checkAvailability($validated['preferred_datetime'], $validated['timezone']);

            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested time slot is not available.',
                    'suggested_times' => $availability['suggested_times'],
                    'requires_rescheduling' => true
                ], 409);
            }

            // Create the demo request
            $demoRequest = DemoRequest::create([
                ...$validated,
                'user_id' => Auth::id(),
                'source' => 'chatbot',
                'status' => 'pending',
            ]);

            Log::info('Demo request created via chatbot', [
                'demo_request_id' => $demoRequest->id,
                'session_id' => $validated['session_id'],
                'email' => $validated['email'],
                'preferred_datetime' => $validated['preferred_datetime']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demo scheduled successfully! You will receive a confirmation email shortly.',
                'demo_request' => $demoRequest
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating demo request via chatbot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to schedule demo at this time. Please try again or contact our support team.'
            ], 500);
        }
    }

    /**
     * Check availability for a given datetime
     */
    public function checkAvailability(string $datetime, string $timezone): array
    {
        try {
            $requestedTime = Carbon::parse($datetime)->setTimezone($timezone);

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
                // Suggest alternative times
                $suggestedTimes = $this->getSuggestedTimes($requestedTime, $timezone);

                return [
                    'available' => false,
                    'suggested_times' => $suggestedTimes
                ];
            }

            return ['available' => true];

        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'datetime' => $datetime,
                'timezone' => $timezone
            ]);

            return ['available' => false, 'suggested_times' => []];
        }
    }

    /**
     * Get suggested alternative times
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
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date|after_or_equal:today',
                'timezone' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $date = Carbon::parse($request->date)->setTimezone($request->timezone);
            $timezone = $request->timezone;

            // Skip weekends
            if ($date->isWeekend()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demos are not available on weekends.'
                ], 400);
            }

            // Generate time slots from 9 AM to 6 PM (every hour)
            $slots = [];
            for ($hour = 9; $hour <= 17; $hour++) {
                $slotTime = $date->copy()->setTime($hour, 0, 0);

                // Skip past times
                if ($slotTime->isPast()) {
                    continue;
                }

                $utcTime = $slotTime->utc();

                // Check availability
                $isAvailable = !DemoRequest::where('status', 'confirmed')
                    ->whereBetween('confirmed_datetime', [
                        $utcTime->copy()->subMinutes(30),
                        $utcTime->copy()->addMinutes(30)
                    ])
                    ->exists();

                $slots[] = [
                    'time' => $slotTime->format('H:i'),
                    'datetime' => $slotTime->toISOString(),
                    'formatted' => $slotTime->format('g:i A'),
                    'available' => $isAvailable
                ];
            }

            return response()->json([
                'success' => true,
                'date' => $date->format('Y-m-d'),
                'timezone' => $timezone,
                'slots' => $slots
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available slots', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch available time slots.'
            ], 500);
        }
    }
}
