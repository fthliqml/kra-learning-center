@extends('layouts.auth')

@section('content')
    <div class="w-full max-w-md">
        <x-card class="shadow-xl border border-base-300">
            <div class="flex items-center gap-3 mb-2">
                <div>
                    <h2 class="card-title">Welcome</h2>
                    <p class="text-sm text-base-content/70">KRA Learning Center</p>
                </div>
            </div>

            @if ($errors->any())
                <x-alert title="Login failed" icon="o-exclamation-triangle" class="alert-error mb-3">
                    {{ __('Please check your credentials and try again.') }}
                </x-alert>
            @endif

            @if (session('status'))
                <x-alert icon="o-check-badge" class="alert-success mb-3">
                    {{ session('status') }}
                </x-alert>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div class="form-control">
                    <label for="email" class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="input input-bordered w-full focus-within:outline-none @error('email') input-error @enderror"
                        placeholder="you@example.com" />
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control">
                    <label for="password" class="label">
                        <span class="label-text">Password</span>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="label-text-alt link link-hover">Forgot
                                password?</a>
                        @endif
                    </label>
                    <input id="password" name="password" type="password" required
                        class="input input-bordered w-full focus-within:outline-none @error('password') input-error @enderror"
                        placeholder="••••••••" />
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                        <span class="label-text">Remember me</span>
                    </label>
                </div>

                <x-button type="submit" class="btn btn-primary w-full" spinner>
                    <x-icon name="o-arrow-right-end-on-rectangle" />
                    <span>Sign in</span>
                </x-button>
            </form>
        </x-card>

        <div class="mt-4 text-center text-sm">
            <span class="text-base-content/70">Don’t have an account?</span>
            <span class="font-semibold"> Contact administrator</span>
        </div>
    </div>
@endsection
