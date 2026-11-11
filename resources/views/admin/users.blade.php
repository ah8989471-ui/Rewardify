@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email or referral code…"
           class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-72">
    <select name="status" class="rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
    </select>
    <button class="rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white">Search</button>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">User</th>
                    <th class="px-5 py-3 font-semibold">Referral code</th>
                    <th class="px-5 py-3 font-semibold">Referred by</th>
                    <th class="px-5 py-3 font-semibold">Referrals</th>
                    <th class="px-5 py-3 text-right font-semibold">Balance</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $u->name }} @if($u->isAdmin())<span class="ml-1 rounded bg-ink px-1.5 py-0.5 text-[10px] font-bold text-white">ADMIN</span>@endif</div>
                            <div class="text-xs text-slate-400">{{ $u->email }}</div>
                        </td>
                        <td class="px-5 py-3"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold">{{ $u->referral_code }}</code></td>
                        <td class="px-5 py-3 text-slate-500">{{ $u->referrer?->referral_code ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $u->referrals_count }}</td>
                        <td class="px-5 py-3 text-right font-bold">${{ number_format((float) $u->balance, 2) }}</td>
                        <td class="px-5 py-3">
                            @if ($u->status === 'suspended')<span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Suspended</span>
                            @elseif (! $u->email_verified_at)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Unverified</span>
                            @else<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Active</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.users.show', $u) }}" class="font-medium text-brand-600 hover:underline">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $users->links() }}</div>
</div>
@endsection
