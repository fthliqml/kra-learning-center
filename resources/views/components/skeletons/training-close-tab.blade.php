<div class="space-y-4 animate-pulse" aria-busy="true">
    {{-- Header Skeleton --}}
    <div class="flex justify-between items-center">
        <div class="h-7 bg-gray-200 rounded w-48"></div>
        <div class="h-9 bg-gray-200 rounded w-64"></div>
    </div>

    {{-- Table Skeleton --}}
    <div class="rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        {{-- Table Header --}}
        <div class="bg-gray-100 border-b border-gray-200">
            <div class="flex gap-3 px-4 py-3">
                <div class="h-3 bg-gray-200 rounded w-12"></div>
                <div class="h-3 bg-gray-200 rounded flex-1"></div>
                <div class="h-3 bg-gray-200 rounded w-28"></div>
                <div class="h-3 bg-gray-200 rounded w-28"></div>
                <div class="h-3 bg-gray-200 rounded w-28"></div>
                <div class="h-3 bg-gray-200 rounded w-20"></div>
                <div class="h-3 bg-gray-200 rounded w-24"></div>
            </div>
        </div>

        {{-- Table Rows Skeleton --}}
        @foreach (range(1, 5) as $r)
            <div class="border-b border-gray-200 {{ $r % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                <div class="flex gap-3 px-4 py-2.5 items-center">
                    <div class="h-4 bg-gray-200 rounded w-8"></div>
                    <div class="h-4 bg-gray-200 rounded flex-1 max-w-xs"></div>
                    <div class="h-8 bg-gray-200 rounded w-20"></div>
                    <div class="h-8 bg-gray-200 rounded w-20"></div>
                    <div class="h-8 bg-gray-200 rounded w-20"></div>
                    <div class="h-4 bg-gray-200 rounded w-12 mx-auto"></div>
                    <div class="h-5 bg-gray-200 rounded w-20"></div>
                </div>
            </div>
        @endforeach

        {{-- Pagination Skeleton --}}
        <div class="px-4 py-3 border-t border-gray-200 flex justify-between items-center">
            <div class="h-4 bg-gray-200 rounded w-32"></div>
            <div class="flex gap-2">
                <div class="h-8 w-8 bg-gray-200 rounded"></div>
                <div class="h-8 w-8 bg-gray-200 rounded"></div>
                <div class="h-8 w-8 bg-gray-200 rounded"></div>
            </div>
        </div>
    </div>

    {{-- Action Buttons Skeleton --}}
    <div class="flex justify-end gap-2 pt-4">
        <div class="h-10 bg-gray-200 rounded w-32"></div>
        <div class="h-10 bg-gray-200 rounded w-40"></div>
    </div>
</div>
