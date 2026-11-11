@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Watching: '.$task->title)
@section('page-title', 'Ad Player')

@section('content')
@php($adTarget = $task->embed_code ? route('tasks.embed', $view->token) : $task->url)
<div class="mx-auto max-w-3xl">
    <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <div class="font-bold text-ink">{{ $task->title }}</div>
                <div class="text-xs text-slate-500">The ad opens in a <strong>new tab</strong>. Stay on the ad tab until the timer finishes — coming back here early stops the timer and shows a warning.</div>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reward</div>
                <div class="text-lg font-extrabold text-emerald-600">+${{ number_format((float) $task->reward, 2) }}</div>
            </div>
        </div>

        <div class="px-5 py-6 text-center">
            <p class="mx-auto max-w-md text-sm text-slate-600">Click the button below. The ad will open in a new browser tab and the timer will start. <strong>Watch time only counts while you are on the ad tab.</strong></p>
            <button id="openAdBtn" class="mt-4 rounded-xl bg-ink px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                ▶ Open ad in new tab &amp; start timer
            </button>
            <p id="manualLinkBox" class="mt-3 hidden text-sm text-slate-600">
                Your browser blocked the new tab.
                <a href="{{ $adTarget }}" target="_blank" rel="noopener" id="manualLink" class="font-semibold text-brand-600 hover:underline">Click here to open the ad</a>
                — the timer still counts only while you are on the ad tab.
            </p>
            <p id="watchingHint" class="mt-3 hidden text-sm font-medium text-emerald-700">Ad is open in the other tab — the timer is running while you watch there. Come back here when it finishes to claim.</p>
        </div>

        <div class="border-t border-slate-100 px-5 py-5">
            <div id="warnBox" class="mb-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                ⚠️ <strong>Warning — you came back before the ad finished!</strong> The timer has <strong>stopped</strong>. Switch back to the ad tab to continue watching; time only counts while you are on the ad tab.
            </div>

            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-slate-600">Watch time remaining</span>
                <span id="countdownLabel" class="font-extrabold text-ink">{{ $task->duration_seconds }}s</span>
            </div>
            <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-200">
                <div id="progressBar" class="h-full w-0 bg-gradient-to-r from-brand-500 to-emerald-500 transition-all duration-200"></div>
            </div>

            <div id="statusBox" class="mt-4 hidden rounded-xl px-4 py-3 text-sm"></div>

            <button id="claimBtn" disabled
                    class="mt-4 w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-slate-300">
                Watch the full ad to claim your reward…
            </button>
            <a href="{{ route('tasks') }}" class="mt-3 block text-center text-sm font-medium text-slate-500 hover:underline">Back to tasks</a>
        </div>
    </div>
</div>

<script>
    const DURATION = {{ $task->duration_seconds }};
    const AD_URL = @json($adTarget);
    const TOKEN = @json($view->token);
    const COMPLETE_URL = @json(route('tasks.complete', $task));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    let started = false;
    let finished = false;
    let claiming = false;
    let watched = 0;          // seconds actually spent on the ad tab
    let hasLeft = false;      // user has switched to the ad tab at least once
    let lastTick = Date.now();
    let winFocused = true;
    let adWindow = null;

    const bar = document.getElementById('progressBar');
    const label = document.getElementById('countdownLabel');
    const claimBtn = document.getElementById('claimBtn');
    const statusBox = document.getElementById('statusBox');
    const warnBox = document.getElementById('warnBox');
    const openBtn = document.getElementById('openAdBtn');
    const hint = document.getElementById('watchingHint');

    function render() {
        label.textContent = Math.ceil(Math.max(0, DURATION - watched)) + 's';
        bar.style.width = Math.min(100, watched / DURATION * 100) + '%';
    }

    // Watch time counts ONLY while this player tab is NOT in front -
    // i.e. while the user is on the ad tab. Returning here early
    // freezes the timer and shows the warning.
    function isWatching() {
        return started && !finished && (document.hidden || !winFocused);
    }

    setInterval(() => {
        const now = Date.now();
        const delta = (now - lastTick) / 1000;
        lastTick = now;
        if (!started || finished) return;

        if (isWatching()) {
            hasLeft = true;
            watched = Math.min(DURATION, watched + delta);
            warnBox.classList.add('hidden');
            render();
            if (watched >= DURATION) {
                finished = true;
                claimBtn.disabled = false;
                claimBtn.textContent = 'Claim ${{ number_format((float) $task->reward, 2) }} reward';
            }
        } else if (hasLeft) {
            // Back on this tab too early: timer is stopped, warn the user.
            warnBox.classList.remove('hidden');
        }
    }, 200);

    window.addEventListener('blur', () => { winFocused = false; });
    window.addEventListener('focus', () => { winFocused = true; lastTick = Date.now(); });

    function beginWatching() {
        if (!started) {
            started = true;
            lastTick = Date.now();
            hint.classList.remove('hidden');
            openBtn.textContent = 'Ad is open — bring the ad tab back to the front';
        }
    }

    openBtn.addEventListener('click', () => {
        if (adWindow && !adWindow.closed) {
            adWindow.focus();
            return;
        }
        adWindow = window.open(AD_URL, '_blank');
        if (!adWindow) {
            document.getElementById('manualLinkBox').classList.remove('hidden');
        }
        beginWatching();
    });
    document.getElementById('manualLink').addEventListener('click', beginWatching);

    claimBtn.addEventListener('click', async () => {
        if (claiming || claimBtn.disabled) return;   // disable double-clicking
        claiming = true;
        claimBtn.disabled = true;
        claimBtn.textContent = 'Crediting…';
        try {
            const res = await fetch(COMPLETE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ token: TOKEN })
            });
            const data = await res.json();
            statusBox.classList.remove('hidden');
            if (data.ok) {
                statusBox.className = 'mt-4 rounded-xl px-4 py-3 text-sm bg-emerald-50 text-emerald-800 border border-emerald-200';
                statusBox.textContent = data.message + ' New balance: $' + data.balance;
                claimBtn.textContent = 'Reward credited ✓';
                warnBox.classList.add('hidden');
            } else {
                statusBox.className = 'mt-4 rounded-xl px-4 py-3 text-sm bg-rose-50 text-rose-800 border border-rose-200';
                statusBox.textContent = data.message || 'Something went wrong.';
                claiming = false;
                claimBtn.disabled = false;
                claimBtn.textContent = 'Try again';
            }
        } catch (e) {
            claiming = false;
            claimBtn.disabled = false;
            claimBtn.textContent = 'Try again';
        }
    });

    render();
</script>
@endsection
