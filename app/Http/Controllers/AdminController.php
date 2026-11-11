<?php

namespace App\Http\Controllers;

use App\Models\AdView;
use App\Models\PostbackLog;
use App\Models\Setting;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /* ---------------------------------------------------------------
     | Analytics overview
     * -------------------------------------------------------------- */

    public function dashboard()
    {
        return view('admin.dashboard', [
            'totalUsers' => User::where('role', 'user')->count(),
            'activeUsers' => User::where('role', 'user')->where('status', 'active')
                ->whereNotNull('email_verified_at')->count(),
            'totalAdsWatched' => AdView::where('status', 'completed')->count(),
            'totalPayouts' => Withdrawal::where('status', 'approved')->sum('amount'),
            'pendingWithdrawals' => Withdrawal::where('status', 'pending')->count(),
            'totalCredited' => Transaction::where('type', 'credit')->sum('amount'),
            'recentUsers' => User::latest()->limit(6)->get(),
            'recentWithdrawals' => Withdrawal::with('user')->latest()->limit(6)->get(),
        ]);
    }

    /* ---------------------------------------------------------------
     | User management
     * -------------------------------------------------------------- */

    public function users(Request $request)
    {
        $query = User::withCount('referrals')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.users', ['users' => $query->paginate(20)->withQueryString()]);
    }

    public function userShow(User $user)
    {
        return view('admin.user-show', [
            'user' => $user->load(['referrer']),
            'referrals' => $user->referrals()->latest()->paginate(10, ['*'], 'ref'),
            'transactions' => $user->transactions()->latest()->paginate(10, ['*'], 'txn'),
            'withdrawals' => $user->withdrawals()->latest()->paginate(10, ['*'], 'wd'),
            'adsWatched' => $user->adViews()->where('status', 'completed')->count(),
            'referralEarningsPaid' => Transaction::where('kind', 'referral_commission')
                ->where('reference', 'ref:'.$user->id)->sum('amount'),
        ]);
    }

    public function userAdjustBalance(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = number_format(abs((float) $data['amount']), 4, '.', '');
        $note = $data['note'] ?? null;
        $description = 'Manual adjustment by admin'.($note ? ': '.$note : '');

        try {
            if ((float) $data['amount'] > 0) {
                $user->credit($amount, 'manual_adjust', $description, 'admin:'.auth()->id());
            } else {
                $user->debit($amount, 'manual_adjust', $description, 'admin:'.auth()->id());
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => 'Cannot debit more than the user\'s balance.']);
        }

        return back()->with('status', 'Balance updated for '.$user->name.'.');
    }

    public function userToggleStatus(User $user)
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['status' => 'Admin accounts cannot be suspended.']);
        }

        $user->forceFill(['status' => $user->status === 'active' ? 'suspended' : 'active'])->save();

        return back()->with('status', $user->name.' is now '.$user->status.'.');
    }

    /* ---------------------------------------------------------------
     | Global settings (incl. WhatsApp fallback number)
     * -------------------------------------------------------------- */

    public function settings()
    {
        return view('admin.settings', ['settings' => $this->currentSettings()]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'site_tagline' => ['nullable', 'string', 'max:120'],
            'admin_whatsapp' => ['required', 'string', 'max:20'],
            'min_withdrawal' => ['required', 'numeric', 'min:0'],
            'default_reward' => ['required', 'numeric', 'min:0'],
            'default_duration' => ['required', 'integer', 'min:5', 'max:600'],
            'referral_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        // Keep digits only so wa.me links always work.
        $data['admin_whatsapp'] = preg_replace('/\D+/', '', $data['admin_whatsapp']);
        if (strlen($data['admin_whatsapp']) < 7) {
            return back()->withErrors(['admin_whatsapp' => 'Enter a valid WhatsApp number with country code (digits only).'])->withInput();
        }

        Setting::setMany($data);

        return back()->with('status', 'Global settings saved. The WhatsApp referral button now uses '.$data['admin_whatsapp'].'.');
    }

    /**
     * Change the signed-in admin's own password from the settings page.
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Admin password changed successfully. Use the new password at your next login.');
    }

    /* ---------------------------------------------------------------
     | CPAGrip integration settings
     * -------------------------------------------------------------- */

    public function cpagrip()
    {
        return view('admin.cpagrip', [
            'settings' => $this->currentSettings(),
            'postbackUrl' => url('/postback/cpagrip'),
            'logs' => PostbackLog::with('user')->latest()->paginate(15),
        ]);
    }

    public function updateCpagrip(Request $request)
    {
        $data = $request->validate([
            'cpagrip_offerwall_url' => ['nullable', 'string', 'max:2048'],
            'cpagrip_iframe_code' => ['nullable', 'string', 'max:10000'],
            'postback_secret' => ['required', 'string', 'min:16', 'max:128'],
        ]);

        Setting::setMany($data);

        return back()->with('status', 'CPAGrip settings saved.');
    }

    public function regenerateSecret()
    {
        Setting::set('postback_secret', Str::random(32));

        return back()->with('status', 'A new postback secret was generated. Update it in CPAGrip too, or existing postbacks will be rejected.');
    }

    private function currentSettings(): array
    {
        $keys = [
            'site_name', 'site_tagline', 'admin_whatsapp', 'min_withdrawal',
            'default_reward', 'default_duration', 'referral_commission_percent',
            'cpagrip_offerwall_url', 'cpagrip_iframe_code', 'postback_secret',
        ];

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = Setting::get($key, '');
        }

        return $values;
    }

    /* ---------------------------------------------------------------
     | Ad tasks management
     * -------------------------------------------------------------- */

    public function tasks()
    {
        return view('admin.tasks', ['tasks' => Task::orderBy('sort_order')->orderBy('id')->paginate(20)]);
    }

    public function taskCreate()
    {
        return view('admin.task-form', [
            'task' => new Task([
                'reward' => Setting::get('default_reward', '0.05'),
                'duration_seconds' => Setting::get('default_duration', '20'),
                'is_active' => true,
            ]),
        ]);
    }

    public function taskStore(Request $request)
    {
        $data = $this->validateTask($request);
        Task::create($data);

        return redirect()->route('admin.tasks')->with('status', 'Task created.');
    }

    public function taskEdit(Task $task)
    {
        return view('admin.task-form', ['task' => $task]);
    }

    public function taskUpdate(Request $request, Task $task)
    {
        $task->update($this->validateTask($request));

        return redirect()->route('admin.tasks')->with('status', 'Task updated.');
    }

    public function taskDestroy(Task $task)
    {
        $task->delete();

        return back()->with('status', 'Task deleted.');
    }

    private function validateTask(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'required_without:embed_code', 'url', 'max:2048'],
            'embed_code' => ['nullable', 'required_without:url', 'string', 'max:5000'],
            'duration_seconds' => ['required', 'integer', 'min:5', 'max:600'],
            'reward' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /* ---------------------------------------------------------------
     | Withdrawals management
     * -------------------------------------------------------------- */

    public function withdrawals(Request $request)
    {
        $query = Withdrawal::with('user')->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.withdrawals', ['withdrawals' => $query->paginate(20)->withQueryString()]);
    }

    public function withdrawalApprove(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => 'This withdrawal was already processed.']);
        }

        $withdrawal->forceFill(['status' => 'approved', 'processed_at' => now()])->save();

        return back()->with('status', 'Withdrawal #'.$withdrawal->id.' approved. Pay the user outside the app and keep your payment receipt.');
    }

    public function withdrawalReject(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => 'This withdrawal was already processed.']);
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        $withdrawal->forceFill([
            'status' => 'rejected',
            'processed_at' => now(),
            'admin_note' => $data['admin_note'] ?? null,
        ])->save();

        // Refund the held amount in full.
        $withdrawal->user->credit(
            (string) $withdrawal->amount,
            'withdrawal_refund',
            'Refund for rejected withdrawal #'.$withdrawal->id,
            'withdrawal:'.$withdrawal->id
        );

        return back()->with('status', 'Withdrawal #'.$withdrawal->id.' rejected and $'.number_format((float) $withdrawal->amount, 2).' refunded to the user.');
    }
}
