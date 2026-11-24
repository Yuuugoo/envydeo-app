@extends('layouts.main')

@section('title', 'Tonight')

@section('content')
<div class="relative isolate">
    <div aria-hidden="true" class="absolute -left-40 -top-24 -z-10 size-[30rem] rounded-full bg-brand-600/15 blur-3xl"></div>
    <div class="shell page-top pb-8">
        <livewire:social.tonight-home />
    </div>
</div>
@endsection
