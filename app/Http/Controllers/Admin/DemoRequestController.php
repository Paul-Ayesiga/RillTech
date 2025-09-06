<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DemoRequestController extends Controller
{
    /**
     * Display a listing of demo requests
     */
    public function index(Request $request): Response
    {
        $query = DemoRequest::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('demo_type')) {
            $query->where('demo_type', $request->demo_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $demoRequests = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = [
            'total' => DemoRequest::count(),
            'pending' => DemoRequest::where('status', 'pending')->count(),
            'confirmed' => DemoRequest::where('status', 'confirmed')->count(),
            'completed' => DemoRequest::where('status', 'completed')->count(),
            'today' => DemoRequest::whereDate('created_at', Carbon::today())->count(),
            'this_week' => DemoRequest::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
        ];

        return Inertia::render('admin/DemoRequests/Index', [
            'demoRequests' => $demoRequests,
            'stats' => $stats,
            'filters' => $request->only(['status', 'demo_type', 'source', 'search']),
        ]);
    }

    /**
     * Show a specific demo request
     */
    public function show(DemoRequest $demoRequest): Response
    {
        $demoRequest->load('user');

        return Inertia::render('admin/DemoRequests/Show', [
            'demoRequest' => $demoRequest,
        ]);
    }

    /**
     * Update demo request status
     */
    public function updateStatus(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,completed,cancelled,rescheduled',
                'confirmed_datetime' => 'nullable|date|after:now',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();

            // If confirming, require confirmed_datetime
            if ($validated['status'] === 'confirmed' && empty($validated['confirmed_datetime'])) {
                return redirect()->back()
                    ->withErrors(['confirmed_datetime' => 'Confirmed datetime is required when confirming a demo.'])
                    ->withInput();
            }

            $demoRequest->update($validated);

            Log::info('Demo request status updated', [
                'demo_request_id' => $demoRequest->id,
                'old_status' => $demoRequest->getOriginal('status'),
                'new_status' => $validated['status'],
                'admin_user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.demo-requests.index')
                ->with('success', 'Demo request updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating demo request status', [
                'error' => $e->getMessage(),
                'demo_request_id' => $demoRequest->id,
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while updating the demo request.');
        }
    }

    /**
     * Bulk update demo requests
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'exists:demo_requests,id',
                'status' => 'required|in:pending,confirmed,completed,cancelled,rescheduled',
                'confirmed_datetime' => 'nullable|date|after:now',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();
            $demoRequestIds = $validated['ids'];
            $status = $validated['status'];

            $updateData = [
                'status' => $status,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ];

            // If confirming, require and set confirmed_datetime
            if ($status === 'confirmed') {
                if (empty($validated['confirmed_datetime'])) {
                    return redirect()->back()
                        ->withErrors(['confirmed_datetime' => 'Confirmed datetime is required for bulk confirmation.'])
                        ->withInput();
                }
                $updateData['confirmed_datetime'] = $validated['confirmed_datetime'];
            }

            // Update the records
            $count = DemoRequest::whereIn('id', $demoRequestIds)->update($updateData);

            Log::info('Bulk demo request update', [
                'status' => $status,
                'count' => $count,
                'demo_request_ids' => $demoRequestIds,
                'admin_user_id' => Auth::id(),
            ]);

            $message = "{$count} demo requests updated successfully.";

            return redirect()->route('admin.demo-requests.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error in bulk demo request update', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred during bulk update.');
        }
    }
}
