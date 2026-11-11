<?php

namespace App\Http\Controllers;

use App\Models\AdView;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Timed ad player with server-side validation.
 *
 * Flow: start() issues a one-time token and records the start time on
 * the SERVER. complete() only credits when (a) the token belongs to
 * the user and is still 'started', and (b) the server-side elapsed
 * time is at least the task duration (minus a 1s network grace).
 * The browser countdown is only a display - it cannot create credit.
 */
class AdPlayerController extends Controller
{
    public function start(Request $request, Task $task)
    {
        $user = $request->user();
        abort_unless($task->is_active, 404);

        // 24-hour cooldown: each ad can be watched once, then it locks
        // for exactly 24 hours counted from the moment it was completed.
        $lastCompleted = $user->adViews()->where('task_id', $task->id)
            ->where('status', 'completed')
            ->latest('completed_at')->first();

        if ($lastCompleted && $lastCompleted->completed_at->gt(now()->subHours(24))) {
            $availableAt = $lastCompleted->completed_at->copy()->addHours(24);

            return redirect()->route('tasks')
                ->withErrors(['task' => '"'.$task->title.'" is on a 24-hour cooldown. You can watch it again at '.$availableAt->format('d M, h:i A').'.']);
        }

        // Only one in-progress view per user: expire older open views.
        $user->adViews()->where('status', 'started')
            ->update(['status' => 'expired']);

        $view = AdView::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'token' => Str::uuid()->toString(),
            'status' => 'started',
            'started_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return view('user.player', ['task' => $task, 'view' => $view]);
    }

    /**
     * Bare page that renders a task's embed code (e.g. an A-Ads unit).
     * The player opens this page in the new tab instead of a raw URL.
     * Only the user who started the view can open it, and only while
     * the view is still in progress.
     */
    public function embed(Request $request, string $token)
    {
        $view = AdView::where('token', $token)
            ->where('user_id', $request->user()->id)
            ->where('status', 'started')
            ->firstOrFail();

        abort_unless($view->task && $view->task->embed_code, 404);

        return view('user.embed', ['task' => $view->task]);
    }

    public function complete(Request $request, Task $task)
    {
        $user = $request->user();

        $data = $request->validate(['token' => ['required', 'string']]);

        $view = AdView::where('token', $data['token'])
            ->where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->first();

        if (! $view || $view->status !== 'started') {
            return response()->json(['ok' => false, 'message' => 'This view is invalid or was already credited.'], 422);
        }

        $elapsed = $view->started_at->diffInSeconds(now());
        if ($elapsed < $task->duration_seconds - 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Timer not finished yet.',
                'remaining' => $task->duration_seconds - $elapsed,
            ], 422);
        }

        $view->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'reward_credited' => $task->reward,
        ])->save();

        $user->credit(
            (string) $task->reward,
            'ad_view',
            'Watched: '.$task->title,
            'adview:'.$view->token
        );
        $user->payReferralCommission((string) $task->reward, 'an ad view');

        return response()->json([
            'ok' => true,
            'message' => 'You earned $'.number_format((float) $task->reward, 2).'!',
            'balance' => number_format((float) $user->fresh()->balance, 2),
        ]);
    }
}
