@props([
    'courseTitle' => 'Course',
])

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
                <h1 class="text-base md:text-lg font-semibold truncate max-w-[360px]">{{ $courseTitle }}</h1>
            </div>
        </div>
    </div>
</header>
