@props([
    'courseTitle' => 'Course',
    'closeRoute' => null,
])

<header class="sticky top-0 z-40 w-full bg-gradient-to-r from-gray-900 via-primary to-primary/70 text-white shadow">
    <div class="mx-auto px-4 md:px-8 h-16 flex items-center gap-5 justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" @click="openSidebar = !openSidebar"
                class="hidden md:inline-flex items-center justify-center size-9 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition"
                aria-label="Toggle Sidebar">
                <x-icon name="o-bars-3" class="size-5" />
            </button>
            <button type="button" @click="mobileSidebar = true"
                class="md:hidden inline-flex items-center justify-center size-9 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition"
                aria-label="Open Sidebar">
                <x-icon name="o-bars-3" class="size-5" />
            </button>
            <div class="min-w-0">
                <h1 class="text-base md:text-lg font-semibold truncate max-w-[360px]">{{ $courseTitle }}</h1>
            </div>
        </div>
        @if ($closeRoute)
            <div class="flex items-center gap-2 hidden sm:inline">
                <a href="{{ $closeRoute }}" wire:navigate
                    class="inline-flex items-center gap-2 text-xs font-medium px-4 py-2.5 rounded-md bg-white/10 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40 transition border border-white/20 backdrop-blur-sm"
                    aria-label="Close Course">
                    <x-icon name="o-x-mark" class="size-4" />
                    <span class="hidden sm:inline">Close Course</span>
                    <span class="sm:hidden">Close</span>
                </a>
            </div>
        @endif
    </div>
</header>
