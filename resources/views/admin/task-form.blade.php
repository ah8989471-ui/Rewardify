@extends('layouts.panel', ['panel' => 'admin'])

@section('title', $task->exists ? 'Edit Task' : 'New Task')
@section('page-title', $task->exists ? 'Edit Task' : 'New Task')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ $task->exists ? route('admin.tasks.update', $task) : route('admin.tasks.store') }}" class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-slate-200/60">
        @csrf
        @if ($task->exists) @method('PUT') @endif

        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
                <input name="title" value="{{ old('title', $task->title) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Description (optional)</label>
                <textarea name="description" rows="2" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $task->description) }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Ad / destination URL</label>
                <input name="url" type="url" value="{{ old('url', $task->url) }}" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="https://…">
                <p class="mt-1 text-xs text-slate-500">For link ads. The link opens in a new tab when a user watches. Not needed if you paste an embed code below.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Embed code (A-Ads and similar networks)</label>
                <textarea name="embed_code" rows="4" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 font-mono text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="<iframe …></iframe>">{{ old('embed_code', $task->embed_code) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Paste the ad unit's HTML embed code (e.g. from A-Ads). It is shown to the user on a dedicated page that opens in a new tab, and it takes priority over the URL.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Timer (seconds)</label>
                    <input name="duration_seconds" type="number" min="5" max="600" value="{{ old('duration_seconds', $task->duration_seconds) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Reward (USD)</label>
                    <input name="reward" type="number" step="0.001" min="0" value="{{ old('reward', $task->reward) }}" required class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Sort order</label>
                    <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $task->sort_order ?? 0) }}" class="w-full rounded-xl border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <p class="rounded-xl bg-slate-50 px-3.5 py-2.5 text-xs text-slate-500 ring-1 ring-slate-200">Every ad follows the platform rule automatically: each user can watch it <strong>once every 24 hours</strong> — after completion a 24-hour timer runs on that ad for that user.</p>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $task->is_active)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Task is active (visible to workers)
            </label>
        </div>

        <div class="mt-6 flex gap-2">
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">{{ $task->exists ? 'Save changes' : 'Create task' }}</button>
            <a href="{{ route('admin.tasks') }}" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
