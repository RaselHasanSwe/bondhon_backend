<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\OptionGroupConfig;
use App\Models\Page;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\SelectOption;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\BroadcastNotificationService;
use App\Services\NotificationService;
use App\Services\PageService;
use App\Services\ProfileCompletionService;
use App\Services\SiteSettingService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * AdminWebController — Blade-based super admin panel.
 * Session auth via 'web' guard (separate from Sanctum API auth).
 */
class AdminWebController extends Controller
{
    // -----------------------------------------------------------------------
    // Auth
    // -----------------------------------------------------------------------

    public function loginForm(): View|RedirectResponse
    {
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'admin') {
            return redirect()->route('admin.web.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('web')->user();

            if ($user->role !== 'admin') {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'Access denied. Admin account required.']);
            }

            $request->session()->regenerate();
            Log::info('[ADMIN WEB - Login] Admin ID: ' . $user->id . ' logged in.');

            return redirect()->route('admin.web.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $adminId = Auth::guard('web')->id();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[ADMIN WEB - Logout] Admin ID: ' . $adminId . ' logged out.');

        return redirect()->route('admin.web.login');
    }

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------

    public function dashboard(): View
    {
        $stats = [
            'total_users'          => User::count(),
            'active_users'         => User::where('is_active', true)->where('is_banned', false)->count(),
            'banned_users'         => User::where('is_banned', true)->count(),
            'new_users_today'      => User::whereDate('created_at', today())->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'total_revenue_bdt'    => (int) Subscription::where('status', 'active')->sum('amount_bdt'),
            'this_month_revenue'   => (int) Subscription::where('status', 'active')
                                        ->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->sum('amount_bdt'),
            'pending_photos'       => \App\Models\ProfilePhoto::where('moderation_status', 'pending')->count(),
            'pending_reports'      => \App\Models\Report::where('status', 'pending')->count(),
        ];

        $recentSubscriptions = Subscription::with(['user:id,name,email', 'subscriptionPlan:id,name'])
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get();

        $revenueByPlan = Subscription::where('status', 'active')
            ->selectRaw('plan, COUNT(*) as count, SUM(amount_bdt) as revenue')
            ->groupBy('plan')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentSubscriptions', 'revenueByPlan'));
    }

    // -----------------------------------------------------------------------
    // Users
    // -----------------------------------------------------------------------

    public function users(Request $request): View
    {
        $query = User::with('profile')->withTrashed();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'banned'   => $query->where('is_banned', true),
                'inactive' => $query->onlyTrashed(),
                default    => $query->where('is_active', true)->where('is_banned', false),
            };
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    // -----------------------------------------------------------------------
    // Subscription Plans (CRUD)
    // -----------------------------------------------------------------------

    public function plans(): View
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('sort_order')->get();

        return view('admin.subscriptions.plans', compact('plans'));
    }

    public function createPlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'plan_type'     => ['nullable', 'string', 'max:50'],
            'price_bdt'     => ['required', 'integer', 'min:0'],
            'duration_qty'  => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', 'in:hour,day,month,year'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['nullable'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $featuresObj = $this->buildFeaturesArray($request->input('features', []));

        $slug = Str::slug($validated['name']);
        $base = $slug;
        $i    = 1;
        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        DB::transaction(fn () => SubscriptionPlan::create([
            'name'          => $validated['name'],
            'slug'          => $slug,
            'description'   => $validated['description'] ?? null,
            'plan_type'     => $validated['plan_type'] ?? '',
            'price_bdt'     => $validated['price_bdt'],
            'duration_qty'  => $validated['duration_qty'],
            'duration_unit' => $validated['duration_unit'],
            'features'      => $featuresObj,
            'is_active'     => $request->has('is_active'),
            'sort_order'    => $validated['sort_order'] ?? 0,
        ]));

        Log::info('[ADMIN WEB - CreatePlan] Admin: ' . Auth::id() . ' created plan: ' . $validated['name']);

        return redirect()->route('admin.web.plans')->with('success', 'Subscription plan created successfully.');
    }

    public function editPlan(int $id): View
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return view('admin.subscriptions.edit_plan', compact('plan'));
    }

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'plan_type'     => ['nullable', 'string', 'max:50'],
            'price_bdt'     => ['required', 'integer', 'min:0'],
            'duration_qty'  => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', 'in:hour,day,month,year'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['nullable'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $featuresObj = $this->buildFeaturesArray($request->input('features', []));

        DB::transaction(fn () => $plan->update([
            'name'          => $validated['name'],
            'description'   => $validated['description'] ?? null,
            'plan_type'     => $validated['plan_type'] ?? '',
            'price_bdt'     => $validated['price_bdt'],
            'duration_qty'  => $validated['duration_qty'],
            'duration_unit' => $validated['duration_unit'],
            'features'      => $featuresObj,
            'is_active'     => $request->has('is_active'),
            'sort_order'    => $validated['sort_order'] ?? 0,
        ]));

        Log::info('[ADMIN WEB - UpdatePlan] Admin: ' . Auth::id() . ' updated plan ID: ' . $id);

        return redirect()->route('admin.web.plans')->with('success', 'Subscription plan updated successfully.');
    }

    public function deletePlan(Request $request, int $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        if ($plan->price_bdt === 0) {
            return back()->with('error', 'Free plans (price ৳0) cannot be deleted. They are required for new user registration.');
        }

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscriptions.');
        }

        $plan->delete();
        Log::info('[ADMIN WEB - DeletePlan] Admin: ' . Auth::id() . ' deleted plan ID: ' . $id);

        return redirect()->route('admin.web.plans')->with('success', 'Plan deleted.');
    }

    // -----------------------------------------------------------------------
    // Site Settings
    // -----------------------------------------------------------------------

    public function settings(Request $request): View
    {
        $service  = new SiteSettingService();
        $settings = $service->all();
        $defs     = SiteSettingService::definitions();

        return view('admin.settings.index', compact('settings', 'defs'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'        => ['nullable', 'string', 'max:100'],
            'currency'         => ['nullable', 'string', 'max:20'],
            'currency_symbol'  => ['nullable', 'string', 'max:10'],
            'contact_email'    => ['nullable', 'email', 'max:150'],
            'contact_phone'    => ['nullable', 'string', 'max:50'],
            'contact_address'  => ['nullable', 'string', 'max:255'],
            'facebook_url'     => ['nullable', 'url', 'max:255'],
            'twitter_url'      => ['nullable', 'url', 'max:255'],
            'instagram_url'    => ['nullable', 'url', 'max:255'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
            'site_logo'        => ['nullable', 'image', 'max:2048'],
            'site_favicon'     => ['nullable', 'image', 'max:512'],
        ]);

        $service = new SiteSettingService();

        // Handle image uploads
        foreach (['site_logo', 'site_favicon'] as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $service->uploadImage($request->file($imageKey), $imageKey);
            }
            unset($validated[$imageKey]);
        }

        $service->update(array_filter($validated, fn ($v) => $v !== null));

        Log::info('[ADMIN WEB - UpdateSettings] Admin: ' . Auth::id() . ' updated site settings.');

        return redirect()->route('admin.web.settings')->with('success', 'Site settings updated successfully.');
    }

    // -----------------------------------------------------------------------
    // Pages Management
    // -----------------------------------------------------------------------

    public function pages(): View
    {
        $pageService = new PageService();
        $pages       = $pageService->all();

        return view('admin.pages.index', compact('pages'));
    }

    public function editPage(int $id): View
    {
        $pageService = new PageService();
        $page        = $pageService->findById($id);

        return view('admin.pages.edit', compact('page'));
    }

    public function updatePage(Request $request, int $id): RedirectResponse
    {
        $pageService = new PageService();
        $page        = $pageService->findById($id);

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'content'          => ['nullable', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_published'     => ['nullable'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_published'] = $request->has('is_published');
        $validated['sort_order']   = $validated['sort_order'] ?? 0;

        $pageService->update($page, $validated);

        Log::info('[ADMIN WEB - UpdatePage] Admin: ' . Auth::id() . ' updated page ID: ' . $id);

        return redirect()->route('admin.web.pages')->with('success', 'Page updated successfully.');
    }

    // -----------------------------------------------------------------------
    // Photo Moderation
    // -----------------------------------------------------------------------

    public function photos(Request $request): View
    {
        $query = ProfilePhoto::with('user:id,name,email')
            ->where('moderation_status', 'pending')
            ->latest();

        $photos = $query->paginate(20)->withQueryString();

        return view('admin.photos.index', compact('photos'));
    }

    public function photoAction(Request $request, int $id): RedirectResponse
    {
        $photo  = ProfilePhoto::with('user')->findOrFail($id);
        $action = $request->input('action');
        $reason = $request->input('reason', '');

        $notifService = new NotificationService();

        if ($action === 'approve') {
            $photo->update([
                'moderation_status' => 'approved',
                'is_approved'       => true,
            ]);

            // Recalculate profile completion
            $completionService = new ProfileCompletionService();
            $completionService->recalculateAndSave($photo->user);

            $notifService->notifyPhotoApproved($photo->user);

            Log::info('[ADMIN WEB - PhotoApprove] Admin: ' . Auth::id() . ' approved photo ID: ' . $id);
            $flash = 'Photo approved successfully.';
        } else {
            $photo->update(['moderation_status' => 'rejected', 'is_approved' => false]);

            // Recalculate profile completion
            $completionService = new ProfileCompletionService();
            $completionService->recalculateAndSave($photo->user);

            $notifService->notifyPhotoRejected($photo->user, $reason);

            Log::info('[ADMIN WEB - PhotoReject] Admin: ' . Auth::id() . ' rejected photo ID: ' . $id);
            $flash = 'Photo rejected.';
        }

        return redirect()->route('admin.web.photos')->with('success', $flash);
    }

    // -----------------------------------------------------------------------
    // Reports
    // -----------------------------------------------------------------------

    public function reports(Request $request): View
    {
        $query = Report::with(['reporter:id,name,email', 'reported:id,name,email'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function dismissReport(int $id): RedirectResponse
    {
        $report = Report::findOrFail($id);
        $report->update(['status' => 'dismissed']);

        Log::info('[ADMIN WEB - DismissReport] Admin: ' . Auth::id() . ' dismissed report ID: ' . $id);

        return redirect()->route('admin.web.reports')->with('success', 'Report dismissed.');
    }

    public function banUserFromReport(Request $request, int $id): RedirectResponse
    {
        $report = Report::with('reported')->findOrFail($id);

        DB::transaction(function () use ($report) {
            $report->reported->update(['is_banned' => true]);
            $report->update(['status' => 'action_taken']);
        });

        Log::info('[ADMIN WEB - BanFromReport] Admin: ' . Auth::id() . ' banned user: ' . $report->reported_id . ' via report ID: ' . $id);

        return redirect()->route('admin.web.reports')->with('success', 'User banned and report marked as action taken.');
    }

    // -----------------------------------------------------------------------
    // Notification History (Admin)
    // -----------------------------------------------------------------------

    /** Notification types sent by admin (not user-to-user). */
    private const ADMIN_NOTIFICATION_TYPES = [
        'broadcast_message',
        'photo_approved',
        'photo_rejected',
        'subscription_expiry',
    ];

    public function notificationHistory(Request $request): View
    {
        $query = DB::table('notifications')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'notifications.notifiable_id')
                     ->where('notifications.notifiable_type', 'App\Models\User');
            })
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.is_read',
                'notifications.read_at',
                'notifications.created_at',
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->whereIn('notifications.type', self::ADMIN_NOTIFICATION_TYPES)
            ->orderByDesc('notifications.created_at');

        // Filters
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('users.name', 'like', "%{$s}%")
                  ->orWhere('users.email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('notifications.type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('notifications.is_read', $request->status === 'read' ? 1 : 0);
        }

        $notifications = $query->paginate(15)->withQueryString();

        // Stats for admin-sent notifications only
        $totalCount  = DB::table('notifications')->whereIn('type', self::ADMIN_NOTIFICATION_TYPES)->count();
        $unreadCount = DB::table('notifications')->whereIn('type', self::ADMIN_NOTIFICATION_TYPES)->where('is_read', false)->count();

        // Type filter dropdown — only admin types that actually exist
        $types = DB::table('notifications')
            ->whereIn('type', self::ADMIN_NOTIFICATION_TYPES)
            ->distinct()->pluck('type')->sort()->values();

        return view('admin.notifications.history', compact('notifications', 'totalCount', 'unreadCount', 'types'));
    }

    /**
     * Per-user notification history (all types, paginated).
     */
    public function userNotifications(Request $request, int $userId): View|\Illuminate\Http\RedirectResponse
    {
        $user = \App\Models\User::withTrashed()->find($userId);
        if (!$user) {
            return redirect()->route('admin.web.users')->with('error', 'User not found.');
        }

        $query = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('is_read', $request->status === 'read' ? 1 : 0);
        }

        $notifications = $query->paginate(15)->withQueryString();
        $totalCount    = DB::table('notifications')->where('notifiable_id', $userId)->count();
        $unreadCount   = DB::table('notifications')->where('notifiable_id', $userId)->where('is_read', false)->count();
        $types         = DB::table('notifications')->where('notifiable_id', $userId)->distinct()->pluck('type')->sort()->values();

        return view('admin.notifications.user', compact('user', 'notifications', 'totalCount', 'unreadCount', 'types'));
    }

    public function notificationView(string $id): View|\Illuminate\Http\RedirectResponse
    {
        $row = DB::table('notifications')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'notifications.notifiable_id')
                     ->where('notifications.notifiable_type', 'App\Models\User');
            })
            ->select(
                'notifications.*',
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->where('notifications.id', $id)
            ->whereIn('notifications.type', self::ADMIN_NOTIFICATION_TYPES)
            ->first();

        if (!$row) {
            return redirect()->route('admin.web.notifications.history')->with('error', 'Notification not found.');
        }

        // Decode the data column; always ensure it's an array
        if (is_string($row->data)) {
            $decoded = json_decode($row->data, true);
            $row->data = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($row->data)) {
            $row->data = [];
        }

        // NOTE: read/unread status reflects the USER's read state — admin viewing does NOT modify it.

        return view('admin.notifications.view', compact('row'));
    }

    // -----------------------------------------------------------------------
    // Broadcast Notifications
    // -----------------------------------------------------------------------

    public function broadcastForm(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'plan_type']);

        return view('admin.notifications.broadcast', compact('plans'));
    }

    public function sendBroadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:150'],
            'message'  => ['required', 'string', 'max:500'],
            'target'   => ['required', 'in:all,free,silver,gold,platinum,specific'],
            'channel'  => ['required', 'in:application,email,both'],
            'user_ids' => ['sometimes', 'nullable', 'string'],
        ]);

        $service = new BroadcastNotificationService(new NotificationService());
        $channel = $validated['channel'];
        $count   = 0;

        if ($validated['target'] === 'specific') {
            $userIds = array_filter(array_map('intval', explode(',', $validated['user_ids'] ?? '')));
            if (empty($userIds)) {
                return back()->withErrors(['user_ids' => 'Please select at least one user.'])->withInput();
            }
            $count = $service->broadcastToSpecificUsers($userIds, $validated['title'], $validated['message'], $channel);
        } else {
            $count = $service->broadcast($validated['title'], $validated['message'], $validated['target'], $channel);
        }

        Log::info('[ADMIN WEB - Broadcast] Admin: ' . Auth::id() . ' broad-casted to ' . $count . ' users. Target: ' . $validated['target'] . ' | Channel: ' . $channel);

        return redirect()->route('admin.web.broadcast')->with('success', "Notification sent to {$count} user(s) via {$channel}.");
    }

    /**
     * AJAX – search users for broadcast multi-select.
     */
    public function usersSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = $request->get('q', '');
        $users = User::where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'email')
            ->limit(30)
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'text' => $u->name . ' (' . $u->email . ')']);

        return response()->json($users);
    }

    // -----------------------------------------------------------------------
    // Contact Messages
    // -----------------------------------------------------------------------

    public function contactMessages(Request $request): View
    {
        $query = ContactMessage::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('subject', 'like', "%{$q}%"));
        }

        $messages = $query->paginate(20)->withQueryString();
        $unreadCount = ContactMessage::where('status', 'new')->count();

        return view('admin.contact.index', compact('messages', 'unreadCount'));
    }

    public function markMessageRead(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['status' => 'read', 'read_at' => now()]);

        return back()->with('success', 'Message marked as read.');
    }

    public function deleteMessage(int $id): RedirectResponse
    {
        ContactMessage::findOrFail($id)->delete();

        return redirect()->route('admin.web.contact-messages')->with('success', 'Message deleted.');
    }

    // -----------------------------------------------------------------------
    // Account — Change Password (Self)
    // -----------------------------------------------------------------------

    public function changePasswordForm(): View
    {
        return view('admin.account.change_password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password'      => ['required', 'string'],
            'new_password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Auth::guard('web')->user();

        if (! \Illuminate\Support\Facades\Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make($request->new_password)]);

        Log::info('[ADMIN WEB - ChangePassword] Admin ID: ' . $admin->id . ' changed their password.');

        return back()->with('success', 'Password changed successfully.');
    }

    // -----------------------------------------------------------------------
    // Select Options (Dynamic Dropdowns)
    // -----------------------------------------------------------------------

    /** @deprecated Kept for backward compat — use OptionGroupConfig instead. */
    private const OPTION_GROUPS = [];

    public function selectOptions(Request $request): View
    {
        // Load ALL group configs ordered by sort_order
        $allConfigs = OptionGroupConfig::orderBy('sort_order')->get()->keyBy('group_key');

        // Build groups array: group_key => label
        $groups = $allConfigs->mapWithKeys(fn($c) => [$c->group_key => $c->label])->toArray();

        $group = $request->get('group', 'all');
        // Search query (search across id, label, value, group_key and parent label)
        $search = trim((string) $request->get('q', ''));

        // Groups with counts
        $groupCounts = SelectOption::selectRaw('group_key, COUNT(*) as cnt')
            ->groupBy('group_key')
            ->pluck('cnt', 'group_key');

        // "All Groups" mode — show every option with group label column
        if ($group === 'all') {
            $allQuery = SelectOption::with('parent')
                ->orderBy('group_key')->orderBy('sort_order');

            if ($search !== '') {
                $term = "%{$search}%";
                $allQuery->where(function ($q) use ($term, $search) {
                    $q->where('label', 'like', $term)
                        ->orWhere('value', 'like', $term)
                        ->orWhere('group_key', 'like', $term);
                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                    $q->orWhereHas('parent', function ($p) use ($term) {
                        $p->where('label', 'like', $term);
                    });
                });
            }

            $allOptions = $allQuery->get();
            // Attach depth=0 for display consistency
            foreach ($allOptions as $opt) { $opt->_depth = 0; }
            return view('admin.select-options.index', [
                'group'          => 'all',
                'config'         => null,
                'groups'         => $groups,
                'options'        => $allOptions->all(),
                    'parentOptions'  => collect(),
                'parentGroupKey' => null,
                'isSelfNested'   => false,
                'isCrossNested'  => false,
                'groupCounts'    => $groupCounts,
                'allConfigs'     => $allConfigs,
                'maxDepth'       => 1,
                    'showGroupCol'   => true,
                    'canAdd'         => false,
            ]);
        }

        // Current group config
        $config   = $allConfigs->get($group);
        $maxDepth = $config ? min((int)$config->max_nesting_depth, 5) : 1;

        // Parent group for this group (from config)
        $parentGroupKey = $config?->parent_group_key;
        $isSelfNested   = $parentGroupKey && $parentGroupKey === $group;
        $isCrossNested  = $parentGroupKey && $parentGroupKey !== $group;

        // Parent options (for cross-nested groups)
        $parentOptions = ($isCrossNested)
            ? SelectOption::where('group_key', $parentGroupKey)->orderBy('sort_order')->get(['id', 'label', 'group_key'])
            : collect();

        // Can we create an option in this group? For cross-nested groups we require at least one
        // parent option to be present in the parent group before creating a child option.
        $canAdd = true;
        if ($isCrossNested && $parentOptions->isEmpty()) {
            $canAdd = false;
        }

        // Apply optional search within the selected group
        $groupQuery = SelectOption::where('group_key', $group)->orderBy('sort_order');
        if ($search !== '') {
            $term = "%{$search}%";
            $groupQuery->where(function ($q) use ($term, $search) {
                $q->where('label', 'like', $term)
                    ->orWhere('value', 'like', $term);
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhereHas('parent', function ($p) use ($term) {
                    $p->where('label', 'like', $term);
                });
            });
        }

        $allInGroup = $groupQuery->get()->keyBy('id');

        // For cross-nested groups the parent_id points to a *different* group (e.g. bd_division → country).
        // Tree recursion (which starts from parent_id = null) would produce zero results in that case.
        // Just show the options as a flat list with depth = 0.
        if ($isCrossNested) {
            $options = $allInGroup->each(function ($opt) { $opt->_depth = 0; $opt->_ancestors = []; })->values()->all();
        } else {
            $options = $this->buildFlatTree($allInGroup, $maxDepth);
        }

        return view('admin.select-options.index', compact(
            'group', 'config', 'groups', 'options', 'parentOptions',
            'parentGroupKey', 'isSelfNested', 'isCrossNested',
            'groupCounts', 'allConfigs', 'maxDepth', 'canAdd'
        ) + ['showGroupCol' => false]);
    }

    /**
     * Build a flat list with depth info from a keyed collection of SelectOption.
     * Handles both self-nesting and cross-group nesting.
     */
    private function buildFlatTree($allInGroup, int $maxDepth = 5): array
    {
        $rows = [];
        $this->recurseTree($allInGroup, null, 0, $maxDepth, $rows, []);
        return $rows;
    }

    private function recurseTree($all, ?int $parentId, int $depth, int $maxDepth, array &$rows, array $ancestors): void
    {
        if ($depth >= $maxDepth) return;
        foreach ($all->where('parent_id', $parentId) as $opt) {
            $opt->_depth     = $depth;
            $opt->_ancestors = $ancestors;
            $rows[] = $opt;
            $this->recurseTree($all, $opt->id, $depth + 1, $maxDepth, $rows,
                array_merge($ancestors, [['id' => $opt->id, 'label' => $opt->label]])
            );
        }
    }

    public function storeSelectOption(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_key'  => ['required', 'string', 'max:60'],
            'parent_id'  => ['nullable', 'exists:select_options,id'],
            'value'      => ['required', 'string', 'max:100'],
            'label'      => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable'],
        ]);

        // Ensure cross-nested groups have a parent selected and enforce depth limit from group config
        $config = OptionGroupConfig::where('group_key', $validated['group_key'])->first();
        $parentGroupKey = $config?->parent_group_key;
        $isCrossNestedReq = $parentGroupKey && $parentGroupKey !== $validated['group_key'];

        if ($isCrossNestedReq && empty($validated['parent_id'])) {
            return back()->withInput()->with('error', "This group requires a parent option from '{$parentGroupKey}'. Please create/select a parent first.");
        }

        // If a parent was provided for a cross-nested group, make sure it belongs to the expected parent group
        if ($isCrossNestedReq && !empty($validated['parent_id'])) {
            $parent = SelectOption::find($validated['parent_id']);
            if (! $parent || $parent->group_key !== $parentGroupKey) {
                return back()->withInput()->with('error', 'Selected parent option is invalid for this group.');
            }
        }

        // Enforce depth limit from group config
        if (!empty($validated['parent_id'])) {
            $maxDepth = $config ? min((int)$config->max_nesting_depth, 5) : 5;
            $depth    = $this->getOptionDepth((int)$validated['parent_id']);
            if ($depth + 1 >= $maxDepth) {
                return back()->with('error', "Max nesting depth ({$maxDepth}) reached for this group.");
            }
        }

        $exists = SelectOption::where('group_key', $validated['group_key'])
            ->where('value', $validated['value'])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->with('error', "A duplicate option already exists: value '{$validated['value']}' in group '{$validated['group_key']}'.");
        }

        $validated['is_active']  = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['parent_id']  = $validated['parent_id'] ?? null;

        SelectOption::create($validated);
        \Illuminate\Support\Facades\Cache::forget("options:{$validated['group_key']}:parent:{$validated['parent_id']}");

        Log::info('[ADMIN WEB - SelectOption Store] Admin: ' . Auth::id() . ' created option group=' . $validated['group_key'] . ' value=' . $validated['value']);

        return back()->with('success', "Option '{$validated['label']}' added successfully.");
    }

    /** Calculate how deep a given option is (0 = root). */
    private function getOptionDepth(int $optionId): int
    {
        $depth = 0;
        $current = SelectOption::find($optionId);
        while ($current && $current->parent_id && $depth < 5) {
            $depth++;
            $current = SelectOption::find($current->parent_id);
        }
        return $depth;
    }

    public function editSelectOption(int $id): View
    {
        $option     = SelectOption::findOrFail($id);
        $allConfigs = OptionGroupConfig::orderBy('sort_order')->get()->keyBy('group_key');
        $groups     = $allConfigs->mapWithKeys(fn($c) => [$c->group_key => $c->label])->toArray();

        $config      = $allConfigs->get($option->group_key);
        $parentGroupKey = $config?->parent_group_key;
        $isSelfNested   = $parentGroupKey && $parentGroupKey === $option->group_key;

        // Parent candidates: for cross-nested → parent group's options
        // For self-nested → all options in the same group EXCEPT the option itself and its descendants
        if ($parentGroupKey && !$isSelfNested) {
            $parentOptions = SelectOption::where('group_key', $parentGroupKey)
                ->orderBy('sort_order')->get(['id', 'label', 'parent_id']);
        } elseif ($isSelfNested) {
            // Exclude self and any descendants
            $excluded = $this->collectDescendants($option->id);
            $excluded[] = $option->id;
            $parentOptions = SelectOption::where('group_key', $option->group_key)
                ->whereNotIn('id', $excluded)
                ->orderBy('sort_order')->get(['id', 'label', 'parent_id']);
        } else {
            $parentOptions = collect();
        }

        return view('admin.select-options.edit', compact(
            'option', 'groups', 'parentOptions', 'parentGroupKey', 'isSelfNested', 'config'
        ));
    }

    /** Recursively collect all descendant IDs of an option. */
    private function collectDescendants(int $id): array
    {
        $ids = [];
        $children = SelectOption::where('parent_id', $id)->pluck('id');
        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->collectDescendants($childId));
        }
        return $ids;
    }

    public function updateSelectOption(Request $request, int $id): RedirectResponse
    {
        $option = SelectOption::findOrFail($id);

        $validated = $request->validate([
            'group_key'  => ['nullable', 'string', 'max:60', 'exists:option_group_configs,group_key'],
            'label'      => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable'],
            'parent_id'  => ['nullable', 'exists:select_options,id'],
        ]);

        $oldGroup    = $option->group_key;
        $oldParentId = $option->parent_id;

        $newGroup = $validated['group_key'] ?? $oldGroup;

        // If the target group requires a parent (cross-nested), ensure a valid parent was provided
        $targetConfig = OptionGroupConfig::where('group_key', $newGroup)->first();
        $targetParentGroupKey = $targetConfig?->parent_group_key;
        $targetIsCrossNested = $targetParentGroupKey && $targetParentGroupKey !== $newGroup;
        if ($targetIsCrossNested && empty($validated['parent_id'])) {
            return back()->withInput()->with('error', "Group '{$newGroup}' requires selecting a parent option from '{$targetParentGroupKey}'.");
        }
        if ($targetIsCrossNested && !empty($validated['parent_id'])) {
            $parent = SelectOption::find($validated['parent_id']);
            if (! $parent || $parent->group_key !== $targetParentGroupKey) {
                return back()->withInput()->with('error', 'Selected parent option is invalid for the chosen group.');
            }
        }

        $option->update([
            'group_key'  => $newGroup,
            'label'      => $validated['label'],
            'sort_order' => $validated['sort_order'] ?? $option->sort_order,
            'is_active'  => $request->has('is_active'),
            'parent_id'  => $validated['parent_id'] ?? null,
        ]);

        \Illuminate\Support\Facades\Cache::forget("options:{$oldGroup}:parent:{$oldParentId}");
        \Illuminate\Support\Facades\Cache::forget("options:{$option->group_key}:parent:{$option->parent_id}");

        Log::info('[ADMIN WEB - SelectOption Update] Admin: ' . Auth::id() . ' updated option ID=' . $id . ' group=' . $option->group_key);

        return redirect()->route('admin.web.select-options.index', ['group' => $option->group_key])
            ->with('success', "Option '{$option->label}' updated.");
    }

    public function destroySelectOption(int $id): RedirectResponse
    {
        $option = SelectOption::findOrFail($id);
        $group  = $option->group_key;
        $label  = $option->label;
        $parentId = $option->parent_id;

        $child_count = SelectOption::where('parent_id', $id)->count();

        $option->delete(); // ON DELETE CASCADE removes children too

        \Illuminate\Support\Facades\Cache::forget("options:{$group}:parent:{$parentId}");

        Log::info('[ADMIN WEB - SelectOption Destroy] Admin: ' . Auth::id() . " deleted option ID={$id} label={$label}" . ($child_count ? " (and {$child_count} children)" : ''));

        return back()->with('success', "Option '{$label}' deleted." . ($child_count ? " {$child_count} child option(s) also removed." : ''));
    }

    public function toggleSelectOption(int $id): RedirectResponse
    {
        $option = SelectOption::findOrFail($id);
        $option->update(['is_active' => !$option->is_active]);

        \Illuminate\Support\Facades\Cache::forget("options:{$option->group_key}:parent:{$option->parent_id}");

        $state = $option->is_active ? 'activated' : 'deactivated';
        Log::info('[ADMIN WEB - SelectOption Toggle] Admin: ' . Auth::id() . " {$state} option ID={$id}");

        return back()->with('success', "Option '{$option->label}' {$state}.");
    }

    // -----------------------------------------------------------------------
    // Option Group Configs (manage group definitions)
    // -----------------------------------------------------------------------

    private const PROFILE_TABS = [
        'basic'       => 'Basic Info',
        'location'    => 'Location',
        'religion'    => 'Religion',
        'career'      => 'Career',
        'lifestyle'   => 'Lifestyle',
        'family'      => 'Family',
        'horoscope'   => 'Horoscope',
        'preferences' => 'Partner Preferences',
        'none'        => 'None (not shown in profile form)',
    ];

    public function optionGroups(): View
    {
        $configs = OptionGroupConfig::orderBy('sort_order')->get();
        $tabs    = self::PROFILE_TABS;
        $allGroupKeys = OptionGroupConfig::pluck('group_key');

        return view('admin.option-groups.index', compact('configs', 'tabs', 'allGroupKeys'));
    }

    public function createOptionGroup(): View
    {
        $tabs        = self::PROFILE_TABS;
        $allGroupKeys = OptionGroupConfig::orderBy('label')->pluck('group_key', 'group_key');
        return view('admin.option-groups.create', compact('tabs', 'allGroupKeys'));
    }

    public function storeOptionGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_key'          => ['required', 'string', 'max:60', 'unique:option_group_configs,group_key', 'regex:/^[a-z0-9_]+$/'],
            'label'              => ['required', 'string', 'max:255'],
            'profile_tab'        => ['required', 'in:' . implode(',', array_keys(self::PROFILE_TABS))],
            'field_name'         => ['nullable', 'string', 'max:100'],
            'input_type'         => ['required', 'in:select,multi_select'],
            'parent_group_key'   => ['nullable', 'exists:option_group_configs,group_key'],
            'max_nesting_depth'  => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable'],
        ]);

        $validated['is_active']  = $request->has('is_active');
        $validated['is_system']  = false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        OptionGroupConfig::create($validated);

        // Clear group config cache
        \Illuminate\Support\Facades\Cache::forget('option_groups:tab:all');
        foreach (array_keys(self::PROFILE_TABS) as $tab) {
            \Illuminate\Support\Facades\Cache::forget("option_groups:tab:{$tab}");
        }

        Log::info('[ADMIN WEB - OptionGroup Store] Admin: ' . Auth::id() . ' created group: ' . $validated['group_key']);

        return redirect()->route('admin.web.option-groups.index')
            ->with('success', "Option group '{$validated['label']}' ({$validated['group_key']}) created. You can now add options to it.");
    }

    public function editOptionGroup(int $id): View
    {
        $config      = OptionGroupConfig::findOrFail($id);
        $tabs        = self::PROFILE_TABS;
        $allGroupKeys = OptionGroupConfig::where('group_key', '!=', $config->group_key)
            ->orderBy('label')->pluck('group_key', 'group_key');

        return view('admin.option-groups.edit', compact('config', 'tabs', 'allGroupKeys'));
    }

    public function updateOptionGroup(Request $request, int $id): RedirectResponse
    {
        $config = OptionGroupConfig::findOrFail($id);

        $validated = $request->validate([
            'label'              => ['required', 'string', 'max:255'],
            'profile_tab'        => ['required', 'in:' . implode(',', array_keys(self::PROFILE_TABS))],
            'field_name'         => ['nullable', 'string', 'max:100'],
            'input_type'         => ['required', 'in:select,multi_select'],
            'parent_group_key'   => ['nullable', 'exists:option_group_configs,group_key'],
            'max_nesting_depth'  => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable'],
        ]);

        $validated['is_active']  = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? $config->sort_order;

        // Can't set parent to itself unless self-nesting is intended
        if (isset($validated['parent_group_key']) && $validated['parent_group_key'] === $config->group_key) {
            // self-nesting — allowed
        }

        $config->update($validated);

        // Clear all group config caches
        \Illuminate\Support\Facades\Cache::forget('option_groups:tab:all');
        foreach (array_keys(self::PROFILE_TABS) as $tab) {
            \Illuminate\Support\Facades\Cache::forget("option_groups:tab:{$tab}");
        }

        Log::info('[ADMIN WEB - OptionGroup Update] Admin: ' . Auth::id() . ' updated group ID: ' . $id);

        return redirect()->route('admin.web.option-groups.index')
            ->with('success', "Option group '{$config->label}' updated.");
    }

    public function destroyOptionGroup(int $id): RedirectResponse
    {
        $config = OptionGroupConfig::findOrFail($id);

        if ($config->is_system) {
            return back()->with('error', "System groups cannot be deleted. You can deactivate them instead.");
        }

        $optionCount = SelectOption::where('group_key', $config->group_key)->count();
        if ($optionCount > 0) {
            return back()->with('error', "Cannot delete group '{$config->label}' — it has {$optionCount} option(s). Delete all options first.");
        }

        $config->delete();

        \Illuminate\Support\Facades\Cache::forget('option_groups:tab:all');
        foreach (array_keys(self::PROFILE_TABS) as $tab) {
            \Illuminate\Support\Facades\Cache::forget("option_groups:tab:{$tab}");
        }

        Log::info('[ADMIN WEB - OptionGroup Destroy] Admin: ' . Auth::id() . ' deleted group: ' . $config->group_key);

        return redirect()->route('admin.web.option-groups.index')
            ->with('success', "Option group '{$config->label}' deleted.");
    }

    public function toggleOptionGroup(int $id): RedirectResponse
    {
        $config = OptionGroupConfig::findOrFail($id);
        $config->update(['is_active' => !$config->is_active]);

        \Illuminate\Support\Facades\Cache::forget('option_groups:tab:all');
        foreach (array_keys(self::PROFILE_TABS) as $tab) {
            \Illuminate\Support\Facades\Cache::forget("option_groups:tab:{$tab}");
        }

        $state = $config->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Group '{$config->label}' {$state}.");
    }

    // -----------------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------------

    /**
     * Build the structured features JSON object from submitted form inputs.
     * Checkboxes send "1" when checked and nothing when unchecked.
     * Qty fields send integer strings.
     * Enum fields send the selected value.
     *
     * @param  array<string, mixed> $raw Submitted features[key] values
     * @return array<string, mixed>
     */
    private function buildFeaturesArray(array $raw): array
    {
        $defs   = SubscriptionFeatureService::definitions();
        $result = [];

        foreach ($defs as $key => $def) {
            if ($def['type'] === 'bool') {
                $result[$key] = isset($raw[$key]) && $raw[$key] === '1';
            } elseif ($def['type'] === 'qty') {
                $result[$key] = isset($raw[$key]) ? (int) $raw[$key] : (int) $def['default'];
            } elseif ($def['type'] === 'enum') {
                $result[$key] = in_array($raw[$key] ?? '', $def['options'])
                    ? $raw[$key]
                    : $def['default'];
            }
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Subscriptions Sales
    // -----------------------------------------------------------------------

    public function subscriptions(Request $request): View
    {
        $query = Subscription::with(['user:id,name,email', 'subscriptionPlan:id,name,plan_type'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
            );
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        $summary = [
            'total_revenue'       => (int) Subscription::where('status', 'active')->sum('amount_bdt'),
            'month_revenue'       => (int) Subscription::where('status', 'active')
                                        ->whereMonth('created_at', now()->month)
                                        ->sum('amount_bdt'),
            'active_count'        => Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'total_count'         => Subscription::where('status', 'active')->count(),
        ];

        return view('admin.subscriptions.index', compact('subscriptions', 'summary'));
    }
}

