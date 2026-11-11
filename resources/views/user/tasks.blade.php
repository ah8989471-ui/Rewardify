@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Watch Ads / Tasks')
@section('page-title', 'Watch Ads / Tasks')

@section('content')
@if ($wallUrl || $wallCode)
    <div class="mb-5 rounded-2xl bg-gradient-to-r from-ink to-brand-900 p-5 text-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-base font-bold">CPAGrip Offerwall</div>
                <p class="text-sm text-white/75">Complete apps, surveys and offers for bigger USD rewards — credited automatically by postback.</p>
            </div>
            <a href="{{ route('offerwall') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-800">Open offerwall</a>
        </div>
    </div>
@endif

<p class="mb-4 text-sm text-slate-500">Each ad can be watched once — after you complete it, a <strong>24-hour timer</strong> starts on that ad and it moves to the end of the list until the timer ends.</p>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($tasks as $task)
        <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60 {{ $task->is_available ? '' : 'opacity-75' }}">
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-bold text-ink">{{ $task->title }}</h3>
                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200">+${{ number_format((float) $task->reward, 2) }}</span>
            </div>
            @if ($task->description)
                <p class="mt-1.5 flex-1 text-sm text-slate-600">{{ $task->description }}</p>
            @endif
            <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $task->duration_seconds }}s timer
                </span>
            </div>
            @if ($task->is_available)
                <a href="{{ route('tasks.watch', $task) }}" class="mt-4 block w-full rounded-xl bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">View Ad</a>
            @else
                <div class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-center">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Available again in</div>
                    <div class="cooldown-timer font-mono text-lg font-extrabold tabular-nums text-ink" data-until="{{ $task->cooldown_until->timestamp }}">--:--:--</div>
                    <div class="text-[11px] text-slate-400">{{ $task->cooldown_until->format('d M Y, h:i A') }}</div>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500 shadow-card ring-1 ring-slate-200/60 sm:col-span-2 xl:col-span-3">
            No tasks available right now. Please check back later.
        </div>
    @endforelse
</div>

<script>
    // Live countdowns on the cooling-down ads. When a timer reaches zero
    // the page reloads once so the ad becomes watchable again.
    const timers = document.querySelectorAll('.cooldown-timer');
    if (timers.length) {
        const pad = n => String(n).padStart(2, '0');
        const tick = () => {
            const now = Math.floor(Date.now() / 1000);
            let anyExpired = false;
            timers.forEach(el => {
                let left = parseInt(el.dataset.until, 10) - now;
                if (left <= 0) { anyExpired = true; left = 0; }
                const h = Math.floor(left / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
                el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            });
            if (anyExpired) location.reload();
        };
        tick();
        setInterval(tick, 1000);
    }
</script>
@endsection
