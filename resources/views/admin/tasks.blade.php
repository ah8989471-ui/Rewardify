@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Ad Tasks')
@section('page-title', 'Ad Tasks')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.tasks.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">+ New task</a>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Title</th>
                    <th class="px-5 py-3 font-semibold">URL</th>
                    <th class="px-5 py-3 font-semibold">Timer</th>
                    <th class="px-5 py-3 text-right font-semibold">Reward</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="px-5 py-3 font-medium">{{ $task->title }}</td>
                        <td class="max-w-[220px] truncate px-5 py-3 text-slate-500">{{ $task->url }}</td>
                        <td class="px-5 py-3">{{ $task->duration_seconds }}s</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-600">${{ number_format((float) $task->reward, 2) }}</td>
                        <td class="px-5 py-3">{{ $task->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.tasks.edit', $task) }}" class="font-medium text-brand-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" class="inline" onsubmit="return confirm('Delete this task?')">
                                @csrf @method('DELETE')
                                <button class="ml-3 font-medium text-rose-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No tasks yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4">{{ $tasks->links() }}</div>
</div>
@endsection
