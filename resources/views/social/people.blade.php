@extends('layouts.main')

@section('title', 'Find people')

@section('content')
<div class="shell page-top pb-8">
    <h1 class="mb-8 text-3xl font-extrabold tracking-tight text-white md:text-4xl">Find people</h1>
    <livewire:social.people />
</div>
@endsection
