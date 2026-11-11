@extends('layouts.panel', ['panel' => 'user'])

@section('title', 'Offerwall')
@section('page-title', 'CPAGrip Offerwall')

@section('content')
<div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-slate-200/60">
    <div class="border-b border-slate-100 px-5 py-4">
        <div class="font-bold text-ink">Complete offers, earn USD</div>
        <p class="text-sm text-slate-500">Rewards are credited automatically to your wallet when CPAGrip confirms completion (usually within a few minutes).</p>
    </div>
    <div class="p-0">
        @if ($wallCode)
            {!! $wallCode !!}
        @elseif ($wallUrl)
            <iframe src="{{ $wallUrl }}" class="h-[75vh] w-full" title="CPAGrip Offerwall" loading="lazy"></iframe>
        @else
            <p class="px-5 py-10 text-center text-sm text-slate-500">The offerwall is not configured yet. Please check back later.</p>
        @endif
    </div>
</div>
@endsection
