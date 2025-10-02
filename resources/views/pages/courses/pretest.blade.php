<div class="p-6 space-y-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Pretest: {{ $course->title }}</h1>
        <a wire:navigate href="{{ route('courses-modules.index', $course) }}"
            class="inline-flex items-center gap-2 rounded-md bg-primary/10 hover:bg-primary/15 text-primary px-4 py-2 text-sm font-medium transition">
            <x-icon name="o-academic-cap" class="size-4" />
            <span>Go to Modules</span>
        </a>
    </div>
    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
        Placeholder konten pretest. Tambahkan soal / instruksi di sini.
    </div>
</div>
