@extends('layouts.app')

@section('content')
    {{-- Welcome Header --}}
    <div class="mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">Hi, {{ auth()->user()->name }}</p>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome Back!</h1>
    </div>

    {{-- Admin/Leader Dashboard with Charts --}}
    @if (Auth::user()->hasAnyRole(['admin', 'leader']))
        @livewire('pages.dashboard.leader-dashboard')
    @endif
@endsection
