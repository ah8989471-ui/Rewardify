@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Withdraw')
@section('page-title', 'Withdraw Funds')

@section('content')
<div class="grid gap-4 lg:grid-cols-5">
    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60 lg:col-span-2">
        <div class="flex items-baseline justify-between">
            <h2 class="font-bold text-ink">Request a withdrawal</h2>
            <span class="text-sm text-slate-500">Balance: <strong class="text-ink">${{ number_format((float) $user->balance, 2) }}</strong></span>
        </div>
        <p class="mt-1 text-sm text-slate-500">Minimum withdrawal: <strong>${{ number_format((float) $minWithdrawal, 2) }}</strong>. The amount is held from your balance immediately and refunded in full if the request is rejected.</p>

        <form method="POST" action="{{ route('withdraw.store') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="method">Payment method</label>
                <select id="method" name="method" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($methods as $key => $label)
                        <option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="account_details">Account details</label>
                <textarea id="account_details" name="account_details" rows="3" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                          placeholder="PayPal email / Payoneer email / USDT wallet address (network) / local wallet number">{{ old('account_details') }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="amount">Amount (USD)</label>
                <input id="amount" name="amount" type="number" step="0.01" min="{{ $minWithdrawal }}" value="{{ old('amount') }}" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="10.00">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700">Submit request</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60 lg:col-span-3">
        <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">My withdrawal requests</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3 font-semibold">Date</th>
                        <th class="px-5 py-3 font-semibold">Method</th>
                        <th class="px-5 py-3 text-right font-semibold">Amount</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $w)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $w->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">{{ $w->methodLabel() }}</td>
                            <td class="px-5 py-3 text-right font-bold">${{ number_format((float) $w->amount, 2) }}</td>
                            <td class="px-5 py-3">
                                @switch($w->status)
                                    @case('approved') <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Approved</span> @break
                                    @case('rejected') <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Rejected</span> @break
                                    @default <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Pending</span>
                                @endswitch
                                @if ($w->admin_note)<div class="mt-1 text-xs text-slate-500">{{ $w->admin_note }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No withdrawal requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $withdrawals->links() }}</div>
    </div>
</div>
@endsection
