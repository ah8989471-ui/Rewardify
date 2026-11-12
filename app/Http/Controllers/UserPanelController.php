<?php

namespace App\Http\Controllers;

use App\Models\PostbackLog;
use App\Models\Setting;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\Request;

class UserPanelController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $todayEarnings = $user->transactions()
            ->where('type', 'credit')
            ->whereIn('kind', Transaction::EARNING_KINDS)
            ->whereDate('created_at', today())
            ->sum('amount');

        $completedTasks = $user->adViews()->where('status', 'completed')->count()
            + PostbackLog::where('user_id', $user->id)->where('status', 'credited')->count();

        $referralEarnings = $user->transactions()
            ->where('type', 'credit')
            ->where('kind', 'referral_commission')
            ->sum('amount');

        $recent = $user->transactions()->latest()->limit(8)->get();

        return view('user.dashboard', [
            'user' => $user,
            'todayEarnings' => $todayEarnings,
            'completedTasks' => $completedTasks,
            'referralEarnings' => $referralEarnings,
            'referralCount' => $user->referrals()->count(),
            'recent' => $recent,
            'minWithdrawal' => Setting::get('min_withdrawal', '10.00'),
        ]);
    }

    public function tasks(Request $request)
    {
        $user = $request->user();

        $tasks = Task::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();

        // Last completion per task drives the 24-hour cooldown: an ad
        // completed less than 24h ago is locked, shows a countdown and
        // moves to the END of the list (available ads stay on top).
        $lastCompleted = $user->adViews()
            ->where('status', 'completed')
            ->whereIn('task_id', $tasks->pluck('id'))
            ->selectRaw('task_id, MAX(completed_at) as last_at')
            ->groupBy('task_id')->pluck('last_at', 'task_id');

        foreach ($tasks as $task) {
            $last = isset($lastCompleted[$task->id]) ? \Illuminate\Support\Carbon::parse($lastCompleted[$task->id]) : null;
            $task->cooldown_until = ($last && $last->gt(now()->subHours(24)))
                ? $last->copy()->addHours(24)
                : null;
            $task->is_available = $task->cooldown_until === null;
        }

        // Available ads first (admin order); cooling-down ads at the
        // end, soonest to unlock first.
        $tasks = $tasks->sortBy(fn ($t) => [
            $t->is_available ? 0 : 1,
            $t->cooldown_until?->timestamp ?? $t->sort_order,
            $t->sort_order,
            $t->id,
        ])->values();

        return view('user.tasks', [
            'tasks' => $tasks,
            'wallUrl' => $this->offerwallUrl($user->id),
            'wallCode' => $this->offerwallCode($user->id),
        ]);
    }

    public function offerwall(Request $request)
    {
        return view('user.offerwall', [
            'wallUrl' => $this->offerwallUrl($request->user()->id),
            'wallCode' => $this->offerwallCode($request->user()->id),
        ]);
    }

    public function bitcotasks(Request $request)
    {
        return view('user.bitcotasks', [
            'wallUrl' => $this->bitcotasksWallUrl($request->user()->id),
        ]);
    }

    /** BitcoTasks offerwall URL with the user id as the wall's subid. */
    public function bitcotasksWallUrl(int $userId): ?string
    {
        $apiKey = trim((string) Setting::get('bitcotasks_api_key', ''));

        return $apiKey === '' ? null : 'https://bitcotasks.com/offerwall/'.$apiKey.'/'.$userId;
    }

    /** CPAGrip offerwall URL with the user id substituted as subid. */
    public function offerwallUrl(int $userId): ?string
    {
        $url = trim((string) Setting::get('cpagrip_offerwall_url', ''));

        return $url === '' ? null : str_replace('{USER_ID}', (string) $userId, $url);
    }

    /** Full iframe embed code (if the admin pasted one) with substitution. */
    public function offerwallCode(int $userId): ?string
    {
        $code = trim((string) Setting::get('cpagrip_iframe_code', ''));

        return $code === '' ? null : str_replace('{USER_ID}', (string) $userId, $code);
    }

    public function wallet(Request $request)
    {
        $transactions = $request->user()->transactions()
            ->latest()->paginate(20);

        return view('user.wallet', [
            'user' => $request->user(),
            'transactions' => $transactions,
        ]);
    }

    public function referrals(Request $request)
    {
        $user = $request->user();

        return view('user.referrals', [
            'user' => $user,
            'referrals' => $user->referrals()->latest()->paginate(15),
            'referralEarnings' => $user->transactions()
                ->where('type', 'credit')->where('kind', 'referral_commission')->sum('amount'),
            'commissionPercent' => Setting::get('referral_commission_percent', '10'),
            'registerLink' => route('register'),
        ]);
    }
}
