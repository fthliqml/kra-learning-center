<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @livewireStyles
</head>

<body class="bg-white min-h-screen">
    <main class="min-h-screen grid place-items-center bg-base-200 p-4">

        @yield('content')

    </main>
    @livewireScripts
</body>

</html>
