<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\User;
use App\Exports\ContactSubmissionsExport;
use App\Jobs\SendContactSubmissionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ContactSubmissionController extends Controller
{
    /**
     * Display a listing of contact submissions.
     */
    public function index()
    {
        try {
            $submissions = ContactSubmission::with('assignedUser')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Get statistics
            $stats = [
                'total' => ContactSubmission::count(),
                'new' => ContactSubmission::where('status', 'new')->count(),
                'in_progress' => ContactSubmission::where('status', 'in_progress')->count(),
                'resolved' => ContactSubmission::where('status', 'resolved')->count(),
                'urgent' => ContactSubmission::where('priority', 'urgent')->count(),
                'today' => ContactSubmission::whereDate('created_at', today())->count(),
                'this_week' => ContactSubmission::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
            ];

            // Get admin users for assignment
            $adminUsers = User::with('roles')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'super-admin']);
                })
                ->select('id', 'name', 'email')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name')->toArray(),
                    ];
                });

            return Inertia::render('admin/contact-submissions/Index', [
                'submissions' => $submissions,
                'stats' => $stats,
                'adminUsers' => $adminUsers,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading contact submissions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error loading contact submissions.']);
        }
    }

    /**
     * Update contact submission status
     */
    public function update(Request $request, ContactSubmission $contactSubmission)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:new,in_progress,resolved,closed',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'assigned_to' => 'nullable|exists:users,id',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $contactSubmission->update($validated);

            Log::info('Contact submission updated by admin', [
                'submission_id' => $contactSubmission->id,
                'admin_id' => Auth::id(),
                'changes' => $validated,
            ]);

            return redirect()->back()->with('success', 'Contact submission updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating contact submission: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error updating contact submission.']);
        }
    }

    /**
     * Send email reply to contact submission
     */
    public function sendReply(Request $request, ContactSubmission $contactSubmission)
    {
        try {
            $validated = $request->validate([
                'reply_message' => 'required|string|min:10|max:5000',
                'status' => 'nullable|in:new,in_progress,resolved,closed',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'assigned_to' => 'nullable|exists:users,id',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $adminUser = Auth::user();

            // Update submission immediately (before queuing email)
            $updateData = array_filter([
                'status' => $validated['status'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // If no status provided but sending email, auto-update to in_progress if currently new
            if (!isset($validated['status']) && $contactSubmission->status === 'new') {
                $updateData['status'] = 'in_progress';
            }

            if (!empty($updateData)) {
                $contactSubmission->update($updateData);
                // Refresh the model to get the latest data
                $contactSubmission->refresh();
            }

            // Dispatch the email job
            SendContactSubmissionReply::dispatch(
                $contactSubmission,
                $validated['reply_message'],
                $adminUser
            );

            Log::info('Contact submission reply queued', [
                'submission_id' => $contactSubmission->id,
                'admin_id' => $adminUser->id,
                'recipient' => $contactSubmission->email,
            ]);

            return redirect()->back()->with('success', 'Email reply has been queued for sending.');

        } catch (\Exception $e) {
            Log::error('Error queuing contact submission reply: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error sending email reply.']);
        }
    }

    /**
     * Remove the specified contact submission.
     */
    public function destroy(ContactSubmission $contactSubmission)
    {
        try {
            $email = $contactSubmission->email;
            $subject = $contactSubmission->subject;
            $contactSubmission->delete();

            Log::info('Contact submission deleted by admin', [
                'email' => $email,
                'subject' => $subject,
                'admin_id' => Auth::id(),
            ]);

            return redirect()->route('admin.contact-submissions.index')
                ->with('success', "Contact submission from {$email} has been deleted.");

        } catch (\Exception $e) {
            Log::error('Error deleting contact submission: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error deleting contact submission.']);
        }
    }

    /**
     * Bulk delete contact submissions
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'exists:contact_submissions,id',
            ]);

            $submissionIds = $validated['submission_ids'];
            $deletedCount = ContactSubmission::whereIn('id', $submissionIds)->delete();

            Log::info('Bulk contact submissions deleted by admin', [
                'count' => $deletedCount,
                'admin_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', "Successfully deleted {$deletedCount} contact submissions.");

        } catch (\Exception $e) {
            Log::error('Error bulk deleting contact submissions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error deleting contact submissions.']);
        }
    }

    /**
     * Export contact submissions to Excel
     */
    public function exportCsv(Request $request)
    {
        try {
            $filters = $request->only(['status', 'priority', 'date_from', 'date_to']);
            $filename = 'contact_submissions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new ContactSubmissionsExport($filters), $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting contact submissions: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error exporting contact submissions.']);
        }
    }
}
