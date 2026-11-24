@extends('layouts.main')

@section('title', 'Feed')

@section('content')
<div class="shell page-top pb-8">
    @if (session('status'))
        <p class="mb-6 rounded-2xl bg-brand-600/15 px-4 py-3 text-sm text-brand-200 ring-1 ring-brand-500/30">{{ session('status') }}</p>
    @endif
    <livewire:social.feed />
</div>
@endsection
