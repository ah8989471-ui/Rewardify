@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Total Balance', '$'.number_format((float) $user->balance, 2), 'All-time available balance', 'text-ink'],
        ['Today\'s Earnings', '$'.number_format((float) $todayEarnings, 2), 'Credited today (USD)', 'text-emerald-600'],
        ['Completed Tasks', number_format($completedTasks), 'Ads watched + offers done', 'text-ink'],
        ['Referral Earnings', '$'.number_format((float) $referralEarnings, 2), $referralCount.' user(s) referred by you', 'text-ink'],
    ] as [$label, $value, $sub, $cls])
        <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</div>
            <div class="mt-1.5 text-2xl font-extrabold {{ $cls }}">{{ $value }}</div>
            <div class="mt-0.5 text-xs text-slate-500">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-5 text-white shadow-card lg:col-span-1">
        <div class="text-sm font-semibold">Your referral code</div>
        <div class="mt-2 flex items-center gap-2">
            <code id="refCode" class="rounded-lg bg-white/15 px-3 py-2 text-lg font-bold tracking-widest">{{ $user->referral_code }}</code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('refCode').textContent.trim()); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500);"
                    class="rounded-lg bg-white/20 px-3 py-2 text-sm font-medium hover:bg-white/30">Copy</button>
        </div>
        <p class="mt-3 text-sm text-white/80">Share it — you earn {{ \App\Models\Setting::get('referral_commission_percent', '10') }}% of everything your referrals earn. Minimum withdrawal is ${{ number_format((float) $minWithdrawal, 2) }}.</p>
        <div class="mt-4 flex gap-2">
            <a href="{{ route('tasks') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-700">Watch ads</a>
            <a href="{{ route('withdraw.index') }}" class="rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/40">Withdraw</a>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60 lg:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-ink">Recent activity</h2>
            <a href="{{ route('wallet') }}" class="text-sm font-medium text-brand-600 hover:underline">View wallet</a>
        </div>
        @forelse ($recent as $txn)
            <div class="flex items-center justify-between border-b border-slate-100 py-2.5 text-sm last:border-0">
                <div class="min-w-0">
                    <div class="truncate font-medium text-slate-700">{{ $txn->description ?? $txn->kindLabel() }}</div>
                    <div class="text-xs text-slate-400">{{ $txn->created_at->format('d M Y, h:i A') }} · {{ $txn->kindLabel() }}</div>
                </div>
                <div class="font-bold {{ $txn->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $txn->type === 'credit' ? '+' : '−' }}${{ number_format((float) $txn->amount, 2) }}
                </div>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-slate-500">No transactions yet. <a href="{{ route('tasks') }}" class="font-medium text-brand-600 hover:underline">Watch your first ad</a> to earn.</p>
        @endforelse
    </div>
</div>
@endsection
