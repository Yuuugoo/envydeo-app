@extends('layouts.main')

@section('title', 'Welcome')

@section('content')
<div class="relative isolate overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem]">
        <div class="absolute left-1/3 top-10 size-[28rem] rounded-full bg-brand-600/20 blur-3xl"></div>
    </div>
    <div class="shell page-top flex justify-center pb-16">
        <div class="w-full max-w-md rounded-3xl bg-ink-900/80 p-6 shadow-2xl shadow-black/40 ring-1 ring-white/10 backdrop-blur-xl sm:p-8">
            <livewire:social.onboarding />
        </div>
    </div>
</div>
@endsection
