<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    public function updateStatus(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,completed,cancelled,rescheduled',
                'confirmed_datetime' => 'nullable|date|after:now',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // If confirming, require confirmed_datetime
            if ($validated['status'] === 'confirmed' && empty($validated['confirmed_datetime'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmed datetime is required when confirming a demo.'
                ], 422);
            }

            $demoRequest->update($validated);

            Log::info('Demo request status updated', [
                'demo_request_id' => $demoRequest->id,
                'old_status' => $demoRequest->getOriginal('status'),
                'new_status' => $validated['status'],
                'admin_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demo request updated successfully.',
                'demo_request' => $demoRequest->fresh()->load('user')
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating demo request status', [
                'error' => $e->getMessage(),
                'demo_request_id' => $demoRequest->id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the demo request.'
            ], 500);
        }
    }

    /**
     * Bulk update demo requests
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'demo_request_ids' => 'required|array|min:1',
                'demo_request_ids.*' => 'exists:demo_requests,id',
                'action' => 'required|in:confirm,complete,cancel,delete',
                'confirmed_datetime' => 'nullable|date|after:now',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $demoRequestIds = $validated['demo_request_ids'];
            $action = $validated['action'];

            $updateData = [];
            $message = '';

            switch ($action) {
                case 'confirm':
                    if (empty($validated['confirmed_datetime'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Confirmed datetime is required for bulk confirmation.'
                        ], 422);
                    }
                    $updateData = [
                        'status' => 'confirmed',
                        'confirmed_datetime' => $validated['confirmed_datetime'],
                        'admin_notes' => $validated['admin_notes'] ?? null,
                    ];
                    $message = 'Demo requests confirmed successfully.';
                    break;

                case 'complete':
                    $updateData = [
                        'status' => 'completed',
                        'admin_notes' => $validated['admin_notes'] ?? null,
                    ];
                    $message = 'Demo requests marked as completed.';
                    break;

                case 'cancel':
                    $updateData = [
                        'status' => 'cancelled',
                        'admin_notes' => $validated['admin_notes'] ?? null,
                    ];
                    $message = 'Demo requests cancelled successfully.';
                    break;

                case 'delete':
                    DemoRequest::whereIn('id', $demoRequestIds)->delete();
                    $message = 'Demo requests deleted successfully.';
                    break;
            }

            if ($action !== 'delete') {
                DemoRequest::whereIn('id', $demoRequestIds)->update($updateData);
            }

            Log::info('Bulk demo request update', [
                'action' => $action,
                'demo_request_ids' => $demoRequestIds,
                'admin_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk demo request update', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk update.'
            ], 500);
        }
    }
}
