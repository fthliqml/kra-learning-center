<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @livewireStyles
</head>

<body class="bg-white min-h-screen" x-data="{ stage: '{{ $stage ?? 'pretest' }}', openSidebar: true, mobileSidebar: false }">
    <!-- Top Bar -->
    <header class="sticky top-0 z-40 w-full bg-gradient-to-r from-gray-900 via-primary to-primary/70 text-white shadow">
        <div class="mx-auto px-4 md:px-8 h-16 flex items-center gap-5">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="openSidebar = !openSidebar"
                    class="hidden md:inline-flex items-center justify-center size-9 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition">
                    <x-icon name="o-bars-3" class="size-5" />
                </button>
                <button type="button" @click="mobileSidebar = true"
                    class="md:hidden inline-flex items-center justify-center size-9 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition">
                    <x-icon name="o-bars-3" class="size-5" />
                </button>
                <div class="min-w-0">
                    <h1 class="text-base md:text-lg font-semibold truncate max-w-[360px]">{{ $courseTitle ?? 'Course' }}
                    </h1>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-2 ml-auto">
                <a href="{{ route('courses.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 text-xs font-medium px-4 py-2.5 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition">
                    <x-icon name="o-x-mark" class="size-4" />
                    <span>Close</span>
                </a>
            </div>
        </div>
    </header>

    <div class="flex mx-auto w-full min-h-[calc(100vh-3.5rem)]">
        <!-- Sidebar -->
        <aside x-show="openSidebar" x-transition.opacity x-cloak
            class="hidden md:flex w-72 shrink-0 border-r border-gray-200 bg-white flex-col">
            <div class="p-4 border-b space-y-3">
                <h2 class="text-sm font-semibold text-gray-900">Course Content</h2>
                @isset($progress)
                    <div class="inline-flex items-center gap-3 w-full">
                        <div class="flex-1 h-9 bg-primary/10 rounded-lg flex items-center px-3">
                            <span class="text-[11px] font-medium tracking-wide text-primary uppercase">Progress</span>
                        </div>
                        <div class="h-9 px-3 rounded-lg bg-gray-900 text-white flex items-center text-[11px] font-semibold">
                            {{ $progress }}%</div>
                    </div>
                @endisset
            </div>
            <nav class="flex-1 overflow-y-auto px-4 py-5">
                <ol class="space-y-1 text-sm font-medium">
                    @foreach ($stages ?? ['pretest', 'module', 'posttest', 'result'] as $idx => $s)
                        <li class="group">
                            <div class="flex items-center justify-between rounded-md px-2 py-2 cursor-default"
                                :class="stage === '{{ $s }}' ? 'text-primary font-semibold' :
                                    'text-gray-700 hover:bg-gray-50'">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-6 text-[11px] font-semibold text-gray-400">{{ $idx + 1 }}.</span>
                                    <span
                                        class="capitalize">{{ $s === 'module' ? 'Learning Module' : ucfirst($s) }}</span>
                                </div>
                                <span class="w-4 h-4 rounded-full border flex items-center justify-center text-[9px]"
                                    :class="stage === '{{ $s }}' ? 'border-primary bg-primary text-white' :
                                        'border-gray-300 text-transparent group-hover:border-primary/40'">•</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>

        <!-- Mobile Sidebar -->
        <div x-show="mobileSidebar" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex md:hidden">
            <div @click="mobileSidebar=false" class="flex-1 bg-black/40 backdrop-blur-sm"></div>
            <aside x-show="mobileSidebar" x-transition.transform class="w-72 bg-white h-full shadow-xl flex flex-col">
                <div class="p-4 border-b flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800">Course Content</h2>
                    <button type="button" @click="mobileSidebar=false"
                        class="inline-flex items-center justify-center size-8 rounded-lg hover:bg-gray-100 text-gray-600"><x-icon
                            name="o-x-mark" class="size-5" /></button>
                </div>
                <nav class="flex-1 overflow-y-auto p-4">
                    <ol class="space-y-3 text-sm">
                        @foreach ($stages ?? ['pretest', 'module', 'posttest', 'result'] as $idx => $s)
                            <li class="flex items-center gap-2"
                                :class="stage === '{{ $s }}' ? 'text-primary font-semibold' : 'text-gray-700'">
                                <span class="w-6 text-xs font-medium text-gray-500">{{ $idx + 1 }}.</span>
                                <span class="capitalize">{{ $s === 'module' ? 'Learning Module' : ucfirst($s) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </aside>
        </div>

        <!-- Content -->
        <main class="flex-1 min-w-0 overflow-y-auto bg-gray-50">
            <div class="max-w-6xl mx-auto px-6 md:px-10 py-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-toast />
    @livewireScripts
</body>

</html>
