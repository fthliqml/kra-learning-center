<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @livewireStyles
</head>

<body class="bg-white min-h-screen" x-data="{ stage: '{{ $stage ?? 'pretest' }}', openSidebar: true, mobileSidebar: false }">
    <!-- Top Bar Component -->
    <x-course-layout.top-bar :course-title="$courseTitle ?? 'Course'" />

    <div class="flex mx-auto w-full h-[calc(100vh-4rem)] overflow-hidden">
        <!-- Desktop Sidebar Component -->
        <x-course-layout.sidebar :stage="$stage ?? 'pretest'" :stages="$stages ?? ['pretest', 'module', 'posttest', 'result']" :progress="$progress ?? null" :modules="$modules ?? collect()"
            :close-route="route('courses.index')" />

        <!-- Mobile Sidebar Component -->
        <x-course-layout.sidebar-mobile :stage="$stage ?? 'pretest'" :stages="$stages ?? ['pretest', 'module', 'posttest', 'result']" :progress="$progress ?? null" :modules="$modules ?? collect()"
            :close-route="route('courses.index')" />

        <!-- Content -->
        <main class="flex-1 min-w-0 h-full overflow-y-auto">
            <div class="mx-auto px-6 md:px-10 py-6 min-h-full">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-toast />
    @livewireScripts
</body>

</html>
