@extends('layouts.app')

@section('content')
    {{-- Welcome Header --}}
    <div class="mb-6">
        <h1 class="text-primary text-3xl md:text-4xl font-bold text-center lg:text-start lg:col-span-2 lg:mb-2">
            Dashboard
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Hi, {{ auth()->user()->name }}</p>
    </div>

    {{-- Admin/Leader/Employee Dashboard --}}
    @if (Auth::user()->hasRole('admin'))
        @livewire('pages.dashboard.admin-dashboard')
    @elseif (Auth::user()->hasAnyRole(['leader', 'instructor']))
        @livewire('pages.dashboard.leader-dashboard')
    @elseif (Auth::user()->hasRole('employee'))
        @livewire('pages.dashboard.employee-dashboard')
    @endif
@endsection
