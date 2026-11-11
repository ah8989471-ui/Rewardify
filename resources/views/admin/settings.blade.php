@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Global Settings')
@section('page-title', 'Global Settings')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
        @csrf
        <h2 class="font-bold text-ink">Site</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Site name</label>
                <input name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tagline</label>
                <input name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
        </div>

        <h2 class="mt-8 font-bold text-ink">WhatsApp referral fallback</h2>
        <p class="mt-1 text-sm text-slate-500">This number powers the <em>"Don't have a referral code? Get it via WhatsApp"</em> button on the registration page. Changing it updates the button everywhere, instantly.</p>
        <div class="mt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Admin WhatsApp number (with country code)</label>
            <input name="admin_whatsapp" value="{{ old('admin_whatsapp', $settings['admin_whatsapp']) }}" required inputmode="tel"
                   class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. 923001234567">
            <p class="mt-1.5 text-xs text-slate-500">Digits only — the <code>+</code>, spaces and dashes are removed automatically. Current button link: <span class="break-all font-medium text-brand-700">{{ \App\Models\Setting::whatsappReferralLink() }}</span></p>
        </div>

        <h2 class="mt-8 font-bold text-ink">Money (USD)</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Minimum withdrawal ($)</label>
                <input name="min_withdrawal" type="number" step="0.01" min="0" value="{{ old('min_withdrawal', $settings['min_withdrawal']) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Referral commission (%)</label>
                <input name="referral_commission_percent" type="number" step="0.1" min="0" max="100" value="{{ old('referral_commission_percent', $settings['referral_commission_percent']) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Default task reward ($)</label>
                <input name="default_reward" type="number" step="0.001" min="0" value="{{ old('default_reward', $settings['default_reward']) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Default task duration (seconds)</label>
                <input name="default_duration" type="number" min="5" max="600" value="{{ old('default_duration', $settings['default_duration']) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
        </div>

        <button class="mt-8 w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto sm:px-8">Save settings</button>
    </form>

    <form method="POST" action="{{ route('admin.settings.password') }}" class="mt-4 rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
        @csrf
        <h2 class="font-bold text-ink">Change admin password</h2>
        <p class="mt-1 text-sm text-slate-500">Changes the password of your admin account ({{ auth()->user()->email }}). You will need the new password at your next login.</p>
        <div class="mt-4 space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                <input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                    <input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Min. 8 characters">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Repeat new password">
                </div>
            </div>
        </div>
        <button class="mt-6 w-full rounded-xl bg-ink px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 sm:w-auto sm:px-8">Change password</button>
    </form>
</div>
@endsection
