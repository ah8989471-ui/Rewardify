@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Referrals')
@section('page-title', 'Referrals')

@section('content')
<div class="grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Your referral code</div>
        <div class="mt-2 text-2xl font-extrabold tracking-widest text-ink">{{ $user->referral_code }}</div>
        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Your referral link</label>
            <div class="flex gap-2">
                <input readonly value="{{ $registerLink }}?ref={{ $user->referral_code }}" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" onclick="this.select()">
                <button onclick="navigator.clipboard.writeText('{{ $registerLink }}?ref={{ $user->referral_code }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500);"
                        class="shrink-0 rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">Copy</button>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-600">You earn <strong>{{ $commissionPercent }}%</strong> of everything your referrals earn from ads and offers — automatically, for life.</p>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total referral earnings</div>
        <div class="mt-2 text-2xl font-extrabold text-emerald-600">${{ number_format((float) $referralEarnings, 2) }}</div>
        <div class="mt-1 text-sm text-slate-500">{{ $referrals->total() }} user(s) joined with your code</div>
    </div>

    <div class="rounded-2xl bg-[#25D366]/10 p-5 ring-1 ring-[#25D366]/30">
        <div class="text-sm font-bold text-ink">Share on WhatsApp</div>
        <p class="mt-1 text-sm text-slate-600">Send your code to friends — remember, they cannot register without one.</p>
        <a target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('Join '.\App\Models\Setting::get('site_name', 'WatchEarn').' and earn USD watching ads! Use my referral code: '.$user->referral_code.' — Register here: '.$registerLink.'?ref='.$user->referral_code) }}"
           class="mt-3 inline-block rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white hover:brightness-95">Share via WhatsApp</a>
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">People you referred</div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[520px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Email</th>
                    <th class="px-5 py-3 font-semibold">Joined</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($referrals as $ref)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="px-5 py-3 font-medium">{{ $ref->name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $ref->email }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $ref->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            @if ($ref->email_verified_at)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">Verified</span>
                            @else<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Unverified</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No referrals yet. Share your code to start earning commissions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $referrals->links() }}</div>
</div>
@endsection
