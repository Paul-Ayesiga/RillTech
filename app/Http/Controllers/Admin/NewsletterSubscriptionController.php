<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use App\Exports\NewsletterSubscriptionsExport;
use App\Jobs\SendBulkNewsletterEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class NewsletterSubscriptionController extends Controller
{
    /**
     * Display a listing of newsletter subscriptions.
     */
    public function index()
    {
        try {
            $subscriptions = NewsletterSubscription::query()
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Get statistics
            $stats = [
                'total' => NewsletterSubscription::count(),
                'active' => NewsletterSubscription::active()->count(),
                'unsubscribed' => NewsletterSubscription::unsubscribed()->count(),
                'today' => NewsletterSubscription::whereDate('created_at', today())->count(),
                'this_week' => NewsletterSubscription::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => NewsletterSubscription::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            return Inertia::render('admin/newsletter-subscriptions/Index', [
                'subscriptions' => $subscriptions,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading newsletter subscriptions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error loading newsletter subscriptions.']);
        }
    }

    /**
     * Display the specified newsletter subscription.
     */
    public function show(NewsletterSubscription $newsletterSubscription)
    {
        try {
            return Inertia::render('admin/newsletter-subscriptions/Show', [
                'subscription' => $newsletterSubscription,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading newsletter subscription: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error loading newsletter subscription.']);
        }
    }

    /**
     * Remove the specified newsletter subscription.
     */
    public function destroy(NewsletterSubscription $newsletterSubscription)
    {
        try {
            $email = $newsletterSubscription->email;
            $newsletterSubscription->delete();

            Log::info('Newsletter subscription deleted by admin', [
                'email' => $email,
                'admin_id' => Auth::id(),
            ]);

            return redirect()->route('admin.newsletter-subscriptions.index')
                ->with('success', "Newsletter subscription for {$email} has been deleted.");

        } catch (\Exception $e) {
            Log::error('Error deleting newsletter subscription: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error deleting newsletter subscription.']);
        }
    }

    /**
     * Bulk delete newsletter subscriptions
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'subscription_ids' => 'required|array',
                'subscription_ids.*' => 'exists:newsletter_subscriptions,id',
            ]);

            $subscriptionIds = $validated['subscription_ids'];
            $deletedCount = NewsletterSubscription::whereIn('id', $subscriptionIds)->delete();

            Log::info('Bulk newsletter subscriptions deleted by admin', [
                'count' => $deletedCount,
                'admin_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', "Successfully deleted {$deletedCount} newsletter subscriptions.");

        } catch (\Exception $e) {
            Log::error('Error bulk deleting newsletter subscriptions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error deleting newsletter subscriptions.']);
        }
    }

    /**
     * Send bulk email to newsletter subscribers
     */
    public function sendBulkEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'subscription_ids' => 'required|array|min:1',
                'subscription_ids.*' => 'exists:newsletter_subscriptions,id',
                'subject' => 'required|string|min:3|max:255',
                'email_content' => 'required|string|min:10|max:10000',
            ]);

            $adminUser = Auth::user();
            $subscriptionIds = $validated['subscription_ids'];

            // Verify all subscriptions are active
            $activeSubscriptions = NewsletterSubscription::whereIn('id', $subscriptionIds)
                ->where('status', 'active')
                ->count();

            if ($activeSubscriptions === 0) {
                return back()->withErrors(['error' => 'No active subscriptions found to send emails to.']);
            }

            // Dispatch the bulk email job
            SendBulkNewsletterEmails::dispatch(
                $subscriptionIds,
                $validated['subject'],
                $validated['email_content'],
                $adminUser
            );

            Log::info('Bulk newsletter email campaign initiated', [
                'total_recipients' => count($subscriptionIds),
                'active_recipients' => $activeSubscriptions,
                'admin_id' => $adminUser->id,
                'subject' => $validated['subject'],
            ]);

            return redirect()->back()->with('success', "Newsletter email campaign has been queued for {$activeSubscriptions} active subscribers.");

        } catch (\Exception $e) {
            Log::error('Error initiating bulk newsletter email campaign: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error sending newsletter emails.']);
        }
    }

    /**
     * Export newsletter subscriptions to Excel
     */
    public function exportCsv(Request $request)
    {
        try {
            $filters = $request->only(['status', 'date_from', 'date_to']);
            $filename = 'newsletter_subscriptions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new NewsletterSubscriptionsExport($filters), $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting newsletter subscriptions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error exporting newsletter subscriptions.']);
        }
    }
}
