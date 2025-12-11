@extends('layouts.app')

@section('content')
    <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start mb-6">
        Welcome {{ Auth::user()->name }}
    </h1>

    {{-- Admin/Leadership Dashboard with Charts --}}
    @if (Auth::user()->hasRole('admin') ||
            in_array(Auth::user()->role, ['section_head', 'dept_head', 'div_head', 'director']))
        @livewire('pages.dashboard.leader-dashboard')
    @endif
@endsection
