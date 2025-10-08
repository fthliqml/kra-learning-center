@extends('layouts.app')

@section('content')
    <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">
        Welcome {{ Auth::user()->name }}
    </h1>
@endsection
