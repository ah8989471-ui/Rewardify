@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Admin Overview')
@section('page-title', 'Analytics Overview')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Total Registered Users', number_format($totalUsers), 'All worker accounts'],
        ['Active Users', number_format($activeUsers), 'Verified & not suspended'],
        ['Total Ads Watched', number_format($totalAdsWatched), 'Completed timed ad views'],
        ['Total Payouts Cleared', '$'.number_format((float) $totalPayouts, 2), 'Approved withdrawals'],
    ] as [$label, $value, $sub])
        <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</div>
            <div class="mt-1.5 text-2xl font-extrabold text-ink">{{ $value }}</div>
            <div class="mt-0.5 text-xs text-slate-500">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
        <div class="text-sm font-bold text-amber-900">Pending withdrawals</div>
        <div class="mt-1 text-2xl font-extrabold text-amber-900">{{ $pendingWithdrawals }}</div>
        <a href="{{ route('admin.withdrawals', ['status' => 'pending']) }}" class="mt-2 inline-block text-sm font-medium text-amber-800 underline">Review now</a>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="text-sm font-bold text-ink">Total credited to users</div>
        <div class="mt-1 text-2xl font-extrabold text-ink">${{ number_format((float) $totalCredited, 2) }}</div>
        <div class="text-xs text-slate-500">All credit transactions, all time</div>
    </div>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
        <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">Newest users</div>
        <ul class="divide-y divide-slate-50">
            @forelse ($recentUsers as $u)
                <li class="flex items-center justify-between px-5 py-3 text-sm">
                    <div class="min-w-0">
                        <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-brand-700 hover:underline">{{ $u->name }}</a>
                        <div class="truncate text-xs text-slate-400">{{ $u->email }} · code {{ $u->referral_code }}</div>
                    </div>
                    <span class="text-xs text-slate-400">{{ $u->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-slate-500">No users yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
        <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">Latest withdrawals</div>
        <ul class="divide-y divide-slate-50">
            @forelse ($recentWithdrawals as $w)
                <li class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <span class="font-medium">{{ $w->user->name }}</span>
                        <span class="text-slate-400">· {{ $w->methodLabel() }}</span>
                    </div>
                    <div class="text-right">
                        <div class="font-bold">${{ number_format((float) $w->amount, 2) }}</div>
                        <div class="text-xs capitalize text-slate-400">{{ $w->status }}</div>
                    </div>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-slate-500">No withdrawals yet.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
