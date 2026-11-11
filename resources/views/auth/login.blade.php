@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60 sm:p-8">
        <h1 class="text-xl font-extrabold text-ink">Welcome back</h1>
        <p class="mt-1 text-sm text-slate-500">Log in to your worker account.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="you@example.com">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Your password">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Remember me
            </label>
            <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Log in</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">New here? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:underline">Create an account</a></p>
    </div>
</div>
@endsection
