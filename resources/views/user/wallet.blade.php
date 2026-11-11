@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Wallet & History')
@section('page-title', 'Wallet & History')

@section('content')
<div class="mb-4 rounded-2xl bg-ink p-5 text-white shadow-card">
    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current balance</div>
    <div class="mt-1 text-3xl font-extrabold">${{ number_format((float) $user->balance, 2) }}</div>
    <a href="{{ route('withdraw.index') }}" class="mt-3 inline-block rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Withdraw funds</a>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">Transaction history</div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Date</th>
                    <th class="px-5 py-3 font-semibold">Description</th>
                    <th class="px-5 py-3 font-semibold">Type</th>
                    <th class="px-5 py-3 text-right font-semibold">Amount</th>
                    <th class="px-5 py-3 text-right font-semibold">Balance after</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $txn->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-5 py-3">{{ $txn->description ?? $txn->kindLabel() }}</td>
                        <td class="px-5 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $txn->kindLabel() }}</span></td>
                        <td class="px-5 py-3 text-right font-bold {{ $txn->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $txn->type === 'credit' ? '+' : '−' }}${{ number_format((float) $txn->amount, 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-600">${{ number_format((float) $txn->balance_after, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $transactions->links() }}</div>
</div>
@endsection
