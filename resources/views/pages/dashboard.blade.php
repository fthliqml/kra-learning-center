@extends('layouts.app')

@section('content')
    {{-- Welcome Header --}}
    <div class="mb-6">
        <h1 class="text-primary text-3xl md:text-4xl font-bold text-center lg:text-start lg:col-span-2 lg:mb-2">
            Dashboard
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Hi, {{ auth()->user()->name }}</p>
    </div>

    @php
        $user = Auth::user();
        $positionRole = strtolower((string) ($user?->role ?? ''));
    @endphp

    {{-- Dashboard by role/position --}}
    @if ($user?->hasRole('admin'))
        @livewire('pages.dashboard.admin-dashboard')
    @elseif ($user?->hasRole('instructor'))
        @livewire('pages.dashboard.instructor-dashboard')
    @elseif (in_array($positionRole, ['section_head', 'dept_head', 'div_head', 'director'], true))
        @livewire('pages.dashboard.leader-dashboard')
    @else
        @livewire('pages.dashboard.employee-dashboard')
    @endif
@endsection
