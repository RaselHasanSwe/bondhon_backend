<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\ProfilePhoto;
use App\Models\Report;
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

    public function loginForm(): View
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
            'title'   => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:500'],
            'target'  => ['required', 'in:all,free,silver,gold,platinum'],
        ]);

        $service = new BroadcastNotificationService(new NotificationService());
        $count   = $service->broadcast($validated['title'], $validated['message'], $validated['target']);

        Log::info('[ADMIN WEB - Broadcast] Admin: ' . Auth::id() . ' broad-casted to ' . $count . ' users. Target: ' . $validated['target']);

        return redirect()->route('admin.web.broadcast')->with('success', "Notification sent to {$count} user(s).");
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

