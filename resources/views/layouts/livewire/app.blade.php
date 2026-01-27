<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @livewireStyles
</head>

<body class="bg-white min-h-screen">
    <main class="flex pt-8 md:pt-12 px-4 sm:px-6 lg:px-8">
        @persist('main-sidebar')
            <x-main-sidebar />
        @endpersist

        <div class="flex-1 pb-5 min-w-0 md:transition-all md:duration-500">
            {{ $slot }}
        </div>
    </main>

    <x-toast />
    @livewireScripts
</body>

</html>
