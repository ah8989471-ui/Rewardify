@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'User: '.$user->name)
@section('page-title', 'Manage User')

@section('content')
<div class="mb-4 flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.users') }}" class="text-sm font-medium text-slate-500 hover:underline">← All users</a>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="text-lg font-extrabold text-ink">{{ $user->name }}</div>
        <div class="text-sm text-slate-500">{{ $user->email }}</div>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-bold">${{ number_format((float) $user->balance, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Referral code</dt><dd><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold">{{ $user->referral_code }}</code></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Referred by</dt><dd>{{ $user->referrer ? $user->referrer->name.' ('.$user->referrer->referral_code.')' : '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Ads watched</dt><dd>{{ $adsWatched }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Email verified</dt><dd>{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'No' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Joined</dt><dd>{{ $user->created_at->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="capitalize font-medium">{{ $user->status }}</dd></div>
        </dl>

        @unless ($user->isAdmin())
            <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="mt-5">
                @csrf
                <button class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold {{ $user->status === 'active' ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                    {{ $user->status === 'active' ? 'Suspend account' : 'Reactivate account' }}
                </button>
            </form>
        @endunless
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="font-bold text-ink">Adjust balance manually</div>
        <p class="mt-1 text-sm text-slate-500">Positive amount credits the user, negative debits (cannot exceed the balance). Every adjustment is written to the user's transaction history.</p>
        <form method="POST" action="{{ route('admin.users.adjust', $user) }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Amount (USD)</label>
                <input type="number" step="0.01" name="amount" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. 5.00 or -2.50">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Note (optional)</label>
                <input type="text" name="note" maxlength="255" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Reason for adjustment">
            </div>
            <button class="w-full rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white">Apply adjustment</button>
        </form>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="font-bold text-ink">Referrals of this user ({{ $referrals->total() }})</div>
        <ul class="mt-3 divide-y divide-slate-50 text-sm">
            @forelse ($referrals as $ref)
                <li class="flex items-center justify-between py-2.5">
                    <div class="min-w-0">
                        <a href="{{ route('admin.users.show', $ref) }}" class="font-medium text-brand-700 hover:underline">{{ $ref->name }}</a>
                        <div class="truncate text-xs text-slate-400">{{ $ref->email }}</div>
                    </div>
                    <span class="text-xs text-slate-400">{{ $ref->created_at->format('d M Y') }}</span>
                </li>
            @empty
                <li class="py-4 text-slate-500">No referrals yet.</li>
            @endforelse
        </ul>
        <div class="mt-2">{{ $referrals->links() }}</div>
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">Recent transactions</div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[620px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Date</th>
                    <th class="px-5 py-3 font-semibold">Description</th>
                    <th class="px-5 py-3 text-right font-semibold">Amount</th>
                    <th class="px-5 py-3 text-right font-semibold">Balance after</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $txn->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-5 py-3">{{ $txn->description ?? $txn->kindLabel() }}</td>
                        <td class="px-5 py-3 text-right font-bold {{ $txn->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $txn->type === 'credit' ? '+' : '−' }}${{ number_format((float) $txn->amount, 2) }}</td>
                        <td class="px-5 py-3 text-right text-slate-600">${{ number_format((float) $txn->balance_after, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $transactions->links() }}</div>
</div>
@endsection
