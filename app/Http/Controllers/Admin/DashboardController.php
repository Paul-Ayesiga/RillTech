<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NewsletterSubscription;
use App\Models\ContactSubmission;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with system insights and activity logs.
     */
    public function index()
    {
        // System Overview Statistics
        $systemStats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'banned' => User::where('status', 'banned')->count(),
                'today' => User::whereDate('created_at', today())->count(),
                'this_week' => User::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
            'newsletter' => [
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
            ],
            'contacts' => [
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
            ],
            'system' => [
                'roles' => Role::count(),
                'permissions' => Permission::count(),
                'stripe_customers' => User::whereNotNull('stripe_id')->count(),
            ]
        ];

        // Recent Activity Logs (last 25 activities)
        $recentActivities = Activity::with(['causer', 'subject'])
            ->latest()
            ->limit(25)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'log_name' => $activity->log_name,
                    'event' => $activity->event,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at,
                    'created_at_human' => $activity->created_at->diffForHumans(),
                    'category' => $this->getActivityCategory($activity->log_name),
                    'icon' => $this->getActivityIcon($activity->log_name, $activity->event),
                    'color' => $this->getActivityColor($activity->log_name, $activity->event),
                ];
            });

        // Weekly user registration trend (last 7 days)
        $userTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('M d'),
                'count' => User::whereDate('created_at', $date)->count(),
            ];
        });

        // Weekly newsletter subscription trend (last 7 days)
        $newsletterTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('M d'),
                'count' => NewsletterSubscription::whereDate('created_at', $date)->count(),
            ];
        });

        // Weekly contact submission trend (last 7 days)
        $contactTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('M d'),
                'count' => ContactSubmission::whereDate('created_at', $date)->count(),
            ];
        });

        // Recent Users (last 5)
        $recentUsers = User::with('roles')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at,
                    'created_at_human' => $user->created_at->diffForHumans(),
                ];
            });

        // Recent Newsletter Subscriptions (last 5)
        $recentNewsletterSubs = NewsletterSubscription::latest()
            ->limit(5)
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'email' => $subscription->email,
                    'name' => $subscription->name,
                    'status' => $subscription->status,
                    'created_at' => $subscription->created_at,
                    'created_at_human' => $subscription->created_at->diffForHumans(),
                ];
            });

        // Recent Contact Submissions (last 5)
        $recentContacts = ContactSubmission::with('assignedUser')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'subject' => $contact->subject,
                    'status' => $contact->status,
                    'priority' => $contact->priority,
                    'assigned_user' => $contact->assignedUser ? [
                        'id' => $contact->assignedUser->id,
                        'name' => $contact->assignedUser->name,
                    ] : null,
                    'created_at' => $contact->created_at,
                    'created_at_human' => $contact->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('admin/Dashboard', [
            'systemStats' => $systemStats,
            'recentActivities' => $recentActivities,
            'trends' => [
                'users' => $userTrend,
                'newsletter' => $newsletterTrend,
                'contacts' => $contactTrend,
            ],
            'recentData' => [
                'users' => $recentUsers,
                'newsletter' => $recentNewsletterSubs,
                'contacts' => $recentContacts,
            ],
        ]);
    }

    /**
     * Get activity category based on log name
     */
    private function getActivityCategory(string $logName): string
    {
        return match($logName) {
            'user_management' => 'User Management',
            'newsletter_management' => 'Newsletter',
            'contact_management' => 'Contact Support',
            'role_management' => 'Roles & Permissions',
            'system' => 'System',
            'authentication' => 'Authentication',
            'default' => 'General',
            default => ucfirst(str_replace('_', ' ', $logName))
        };
    }

    /**
     * Get activity icon based on log name and event
     */
    private function getActivityIcon(string $logName, ?string $event): string
    {
        return match($logName) {
            'user_management' => match($event) {
                'created' => 'user-plus',
                'updated' => 'user-check',
                'deleted' => 'user-x',
                default => 'users'
            },
            'newsletter_management' => match($event) {
                'created' => 'mail-plus',
                'updated' => 'mail-check',
                'deleted' => 'mail-x',
                default => 'mail'
            },
            'contact_management' => match($event) {
                'created' => 'message-square-plus',
                'updated' => 'message-square-check',
                'deleted' => 'message-square-x',
                default => 'message-square'
            },
            'role_management' => 'shield',
            'system' => 'settings',
            'authentication' => match($event) {
                'created' => 'log-in',
                'deleted' => 'log-out',
                default => 'key'
            },
            default => 'activity'
        };
    }

    /**
     * Get activity color based on log name and event
     */
    private function getActivityColor(string $logName, ?string $event): string
    {
        return match($event) {
            'created' => 'text-green-600 dark:text-green-400',
            'updated' => 'text-blue-600 dark:text-blue-400',
            'deleted' => 'text-red-600 dark:text-red-400',
            default => match($logName) {
                'user_management' => 'text-purple-600 dark:text-purple-400',
                'newsletter_management' => 'text-indigo-600 dark:text-indigo-400',
                'contact_management' => 'text-orange-600 dark:text-orange-400',
                'role_management' => 'text-emerald-600 dark:text-emerald-400',
                'system' => 'text-gray-600 dark:text-gray-400',
                'authentication' => 'text-cyan-600 dark:text-cyan-400',
                default => 'text-gray-600 dark:text-gray-400'
            }
        };
    }
}
