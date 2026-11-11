@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Withdrawals')
@section('page-title', 'Withdrawal Management')

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
        <a href="{{ route('admin.withdrawals', $val ? ['status' => $val] : []) }}"
           class="rounded-full px-4 py-2 text-sm font-medium ring-1 {{ request('status', '') === $val ? 'bg-ink text-white ring-ink' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Date</th>
                    <th class="px-5 py-3 font-semibold">User</th>
                    <th class="px-5 py-3 font-semibold">Method & account</th>
                    <th class="px-5 py-3 text-right font-semibold">Amount</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($withdrawals as $w)
                    <tr class="border-b border-slate-50 align-top last:border-0">
                        <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $w->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.users.show', $w->user) }}" class="font-medium text-brand-700 hover:underline">{{ $w->user->name }}</a>
                            <div class="text-xs text-slate-400">{{ $w->user->email }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $w->methodLabel() }}</div>
                            <div class="max-w-[240px] break-words text-xs text-slate-500">{{ $w->account_details }}</div>
                        </td>
                        <td class="px-5 py-3 text-right font-bold">${{ number_format((float) $w->amount, 2) }}</td>
                        <td class="px-5 py-3 capitalize">{{ $w->status }}@if($w->admin_note)<div class="mt-1 max-w-[180px] text-xs normal-case text-slate-500">{{ $w->admin_note }}</div>@endif</td>
                        <td class="px-5 py-3">
                            @if ($w->status === 'pending')
                                <div class="flex flex-col items-end gap-2">
                                    <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}" onsubmit="return confirm('Approve withdrawal #{{ $w->id }}? Pay the user outside the app.')">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" class="flex items-center gap-1.5" onsubmit="return confirm('Reject and refund withdrawal #{{ $w->id }}?')">
                                        @csrf
                                        <input name="admin_note" placeholder="Reason (optional)" class="w-36 rounded-lg border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">{{ $w->processed_at?->format('d M, h:i A') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No withdrawal requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $withdrawals->links() }}</div>
</div>
@endsection
