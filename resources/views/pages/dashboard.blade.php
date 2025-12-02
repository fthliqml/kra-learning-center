@extends('layouts.app')

@section('content')
    <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start mb-6">
        Welcome {{ Auth::user()->name }}
    </h1>

    {{-- Admin/Leader Dashboard with Charts --}}
    @if (Auth::user()->hasAnyRole(['admin', 'leader']))
        @livewire('pages.dashboard.leader-dashboard')
    @endif
@endsection
