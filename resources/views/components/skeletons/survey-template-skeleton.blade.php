{{-- Skeleton Loading --}}
<div wire:loading.grid class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
    @foreach (range(1, 9) as $i)
        <div
            class="card bg-base-100 border border-primary/20 shadow h-44 transition duration-200 cursor-pointer animate-pulse">
            <div class="card-body p-4 md:p-5 flex flex-col h-full">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="h-4 bg-gray-300 rounded w-2/3"></div>
                    <div class="h-4 bg-gray-300 rounded w-1/6"></div>
                </div>
                {{-- Description --}}
                <div class="h-3 bg-gray-300 rounded w-full mb-2"></div>
                <div class="h-3 bg-gray-300 rounded w-5/6 mb-2"></div>
                {{-- Level Badge --}}
                <div class="mt-auto flex gap-2">
                    <div class="h-5 w-16 bg-gray-300 rounded"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
