{{-- Skeleton Cards Grid --}}
<div wire:loading.grid class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
    @foreach (range(1, 9) as $i)
        <div class="card bg-base-100 border border-primary/20 shadow h-48 animate-pulse">
            <div class="card-body p-4 md:p-5 flex flex-col h-full">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-2 w-full">
                        <div class="h-4 bg-gray-300 rounded w-3/4"></div> {{-- title --}}
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div> {{-- date --}}
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div> {{-- date --}}
                    </div>
                    <div class="h-4 w-16 bg-gray-300 rounded"></div> {{-- badge --}}
                </div>

                <div class="mt-auto pt-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="h-3 w-12 bg-gray-200 rounded"></div> {{-- type --}}
                        <div class="h-3 w-16 bg-gray-200 rounded"></div> {{-- groupComp --}}
                    </div>
                    <div class="h-8 w-24 bg-gray-300 rounded"></div> {{-- button --}}
                </div>
            </div>
        </div>
    @endforeach
</div>
