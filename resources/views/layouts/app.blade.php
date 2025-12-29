<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @livewireStyles
</head>

<body class="bg-white min-h-screen">
    <main class="flex pt-8 md:pt-12 px-6 md:px-15">
        <x-main-sidebar />

        <div class="flex-1 pb-5 min-w-0">
            @yield('content')
        </div>

    </main>
    @livewireScripts
    @stack('scripts')
</body>

</html>
