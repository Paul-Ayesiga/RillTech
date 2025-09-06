<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Events\userContacted;

class ContactController extends Controller
{
    /**
     * Show contact form
     */
    public function show()
    {
        return Inertia::render('frontend/Contact');
    }

    /**
     * Submit contact form
     */
    public function submit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:5000',
                'source' => 'nullable|string|max:50',
                'priority' => 'nullable|in:low,medium,high,urgent',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please check your form data and try again.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                return back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            // Determine priority based on keywords in subject/message
            $priority = $this->determinePriority($validated['subject'], $validated['message']);
            if (isset($validated['priority'])) {
                $priority = $validated['priority'];
            }

            // Create contact submission
            $submission = ContactSubmission::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $priority,
                'source' => $validated['source'] ?? 'contact_form',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log submission for analytics
            Log::info('Contact form submission', [
                'id' => $submission->id,
                'email' => $validated['email'],
                'subject' => $validated['subject'],
                'priority' => $priority,
                'source' => $validated['source'] ?? 'contact_form',
                'ip' => $request->ip(),
            ]);

            // TODO: Send notification email to admin
            // TODO: Send auto-reply email to user
            event(new userContacted($submission));

            $message = 'Thank you for contacting us! We will get back to you within 24 hours.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'submission_id' => $submission->id
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Contact form submission error: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Sorry, there was an error sending your message. Please try again or contact us directly.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Determine priority based on content
     */
    private function determinePriority(string $subject, string $message): string
    {
        $content = strtolower($subject . ' ' . $message);

        $urgentKeywords = ['urgent', 'emergency', 'critical', 'asap', 'immediately', 'broken', 'down', 'not working'];
        $highKeywords = ['important', 'priority', 'soon', 'quickly', 'issue', 'problem', 'bug', 'error'];
        $lowKeywords = ['question', 'inquiry', 'information', 'demo', 'quote', 'pricing'];

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return 'urgent';
            }
        }

        foreach ($highKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return 'high';
            }
        }

        foreach ($lowKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return 'low';
            }
        }

        return 'medium';
    }
}
