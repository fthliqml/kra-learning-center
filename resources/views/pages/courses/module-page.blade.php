<div class="p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Learning Modules</h1>
        <p class="mt-1 text-sm text-gray-600">Halaman contoh untuk menguji sidebar stage "module". Silakan pilih module
            di sidebar atau kembali ke Pretest.</p>
    </div>

    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
        Placeholder konten learning module. Implementasi detail materi dapat ditambahkan nanti.
    </div>

    <div class="flex gap-3">
        <a wire:navigate href="{{ route('courses-pretest.index', $course) }}"
            class="inline-flex items-center gap-2 rounded-md bg-primary/10 px-4 py-2 text-sm font-medium text-primary hover:bg-primary/15">
            <x-icon name="o-arrow-left" class="size-4" />
            <span>Kembali ke Pretest</span>
        </a>
    </div>
</div>
