@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'CPAGrip Integration')
@section('page-title', 'CPAGrip Integration')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.cpagrip.update') }}" class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
        @csrf
        <h2 class="font-bold text-ink">Offerwall</h2>
        <p class="mt-1 text-sm text-slate-500">Option A — paste your CPAGrip offerwall URL. Use <code class="rounded bg-slate-100 px-1">{USER_ID}</code> where the user id (subid) must go; it is replaced per user automatically.</p>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Offerwall URL</label>
            <input name="cpagrip_offerwall_url" value="{{ old('cpagrip_offerwall_url', $settings['cpagrip_offerwall_url']) }}" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="https://www.cpagrip.com/common/offerwall.php?user_id=XXXX&subid={USER_ID}">
        </div>
        <p class="mt-4 text-sm text-slate-500">Option B — or paste a full iframe embed code (also supports <code class="rounded bg-slate-100 px-1">{USER_ID}</code>). If both are set, the embed code wins.</p>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Iframe embed code</label>
            <textarea name="cpagrip_iframe_code" rows="4" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder='<iframe src="..."></iframe>'>{{ old('cpagrip_iframe_code', $settings['cpagrip_iframe_code']) }}</textarea>
        </div>

        <h2 class="mt-8 font-bold text-ink">Postback security</h2>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Postback secret key</label>
            <input name="postback_secret" value="{{ old('postback_secret', $settings['postback_secret']) }}" required minlength="16" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <button class="mt-6 w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto sm:px-8">Save CPAGrip settings</button>
    </form>

    <div>
        <div class="rounded-2xl bg-ink p-6 text-white shadow-card">
            <h2 class="font-bold">Your postback URL</h2>
            <p class="mt-1 text-sm text-white/70">Paste this into CPAGrip as the postback URL for your offerwall:</p>
            <code class="mt-3 block break-all rounded-xl bg-white/10 p-3 text-xs leading-relaxed">{{ $postbackUrl }}?key={{ $settings['postback_secret'] }}&user_id={subid}&payout={payout}&txn_id={transaction_id}&offer_id={offer_id}</code>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-white/70">
                <li><code>key</code> must match the secret on the left — anything else is rejected.</li>
                <li>Each <code>txn_id</code> is credited once only; replays are logged as duplicates.</li>
                <li>Referral commission is paid automatically on postback earnings too.</li>
            </ul>
            <form method="POST" action="{{ route('admin.cpagrip.secret') }}" class="mt-4" onsubmit="return confirm('Generate a new secret? You must update CPAGrip with it too.')">
                @csrf
                <button class="rounded-xl bg-white/15 px-4 py-2 text-sm font-medium ring-1 ring-white/30 hover:bg-white/25">Regenerate secret</button>
            </form>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
            <div class="border-b border-slate-100 px-5 py-4 font-bold text-ink">Recent postbacks</div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[480px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3 font-semibold">When</th>
                            <th class="px-5 py-3 font-semibold">User</th>
                            <th class="px-5 py-3 font-semibold">Txn</th>
                            <th class="px-5 py-3 text-right font-semibold">Payout</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="whitespace-nowrap px-5 py-2.5 text-slate-500">{{ $log->created_at->format('d M, h:i A') }}</td>
                                <td class="px-5 py-2.5">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-5 py-2.5 font-mono text-xs">{{ $log->txn_id ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-right font-semibold">${{ number_format((float) $log->payout, 2) }}</td>
                                <td class="px-5 py-2.5 capitalize">{{ $log->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">No postbacks received yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $logs->links() }}</div>
        </div>
    </div>
</div>

{{-- ---------------------------------------------------------------
     | BitcoTasks (PTC ads + CPA offers + surveys + tasks in one wall)
     * -------------------------------------------------------------- --}}
<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.bitcotasks.update') }}" class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
        @csrf
        <h2 class="font-bold text-ink">BitcoTasks</h2>
        <p class="mt-1 text-sm text-slate-500">Paste the API key and S2S secret from BitcoTasks (My Apps → Edit). The wall shows PTC ads, CPA offers, surveys and tasks; rewards are paid in USD (currency USD, exchange rate 1).</p>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">API key</label>
            <input name="bitcotasks_api_key" value="{{ old('bitcotasks_api_key', $settings['bitcotasks_api_key']) }}" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Your BitcoTasks app API key">
        </div>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">S2S secret key</label>
            <input type="password" name="bitcotasks_secret" value="" minlength="16" autocomplete="off" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="{{ $settings['bitcotasks_secret'] !== '' ? 'Secret saved — leave blank to keep it' : 'Paste your S2S secret key' }}">
            @if ($settings['bitcotasks_secret'] !== '')
                <p class="mt-1 text-xs font-medium text-emerald-600">A secret is saved (it is never shown). Leave the field blank to keep it.</p>
            @endif
        </div>
        <button class="mt-6 w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto sm:px-8">Save BitcoTasks settings</button>
    </form>

    <div class="rounded-2xl bg-ink p-6 text-white shadow-card">
        <h2 class="font-bold">Your BitcoTasks postback URL</h2>
        <p class="mt-1 text-sm text-white/70">Paste this into BitcoTasks (Add New App → Config → Postback URL):</p>
        <code class="mt-3 block break-all rounded-xl bg-white/10 p-3 text-xs leading-relaxed">{{ $bitcotasksPostbackUrl }}</code>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-white/70">
            <li>Every postback is verified with <code>md5(subId.transId.reward.secret)</code> — anything else is rejected.</li>
            <li>Each <code>transId</code> is credited once only; replays are logged as duplicates.</li>
            <li>A chargeback (<code>status=2</code>) subtracts the reward once; test postbacks (<code>debug=1</code>) change nothing.</li>
            <li>Referral commission is paid automatically on BitcoTasks earnings too.</li>
            <li>All BitcoTasks postbacks appear in the Recent postbacks table above.</li>
        </ul>
    </div>
</div>
@endsection
