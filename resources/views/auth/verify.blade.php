@extends('layouts.guest')

@section('title', 'Verify your email')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60 sm:p-8">
        <h1 class="text-xl font-extrabold text-ink">Verify your email</h1>
        <p class="mt-1 text-sm text-slate-500">Enter the 6-digit code sent to your email. It expires in 15 minutes.
            @if (config('mail.default') === 'log')<span class="block pt-1 text-amber-600">(Local mode: the code is written to <code>storage/logs/laravel.log</code>.)</span>@endif
        </p>

        <form method="POST" action="{{ route('verification.verify') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="code">6-digit code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-3 text-center text-2xl font-bold tracking-[0.5em] shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="••••••">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Verify & continue</button>
        </form>

        <form method="POST" action="{{ route('verification.resend') }}" class="mt-4">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button type="submit" class="w-full rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200">Resend code</button>
        </form>
    </div>
</div>
@endsection
