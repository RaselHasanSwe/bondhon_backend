<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
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

