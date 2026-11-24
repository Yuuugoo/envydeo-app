@extends('layouts.main')

@section('title', 'Reports')

@section('content')
<div class="shell page-top max-w-3xl pb-8">
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Admin</p>
    <h1 class="mb-8 mt-2 text-3xl font-extrabold tracking-tight text-white">Reports</h1>
    <livewire:social.reports-admin />
</div>
@endsection
