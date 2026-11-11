@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60 sm:p-8">
        <h1 class="text-xl font-extrabold text-ink">Create your account</h1>
        <p class="mt-1 text-sm text-slate-500">A referral code is required to join {{ \App\Models\Setting::get('site_name', 'WatchEarn') }}.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Your name">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="you@example.com">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">Password</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Min. 8 characters">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Repeat password">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="referral_code">Referral code <span class="text-rose-600">*</span></label>
                <input id="referral_code" name="referral_code" type="text" value="{{ old('referral_code', request('ref')) }}" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. ADMIN001">
                <p class="mt-1.5 text-xs text-slate-500">Registration is not possible without a valid referral code.</p>

                {{-- Dynamic WhatsApp fallback: number comes from Admin > Global Settings --}}
                <a href="{{ \App\Models\Setting::whatsappReferralLink() }}" target="_blank" rel="noopener"
                   class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:brightness-95">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91A9.86 9.86 0 0012.04 2zm0 18.13h-.01a8.2 8.2 0 01-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 01-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24a8.2 8.2 0 018.24 8.24c0 4.54-3.7 8.24-8.24 8.24zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.13-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.37-.43.12-.15.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.13-.56-1.35-.77-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.13.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.6.19 1.13.16 1.56.1.48-.07 1.47-.58 1.74-1.14.27-.56.27-1.04.19-1.14-.08-.1-.23-.16-.48-.28z"/></svg>
                    Don't have a referral code? Get it via WhatsApp
                </a>
            </div>

            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Create account</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Log in</a></p>
    </div>
</div>
@endsection
