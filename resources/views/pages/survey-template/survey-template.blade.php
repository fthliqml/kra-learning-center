<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Survey Template
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <x-ui.button variant="primary" wire:click="addPage" wire:target="addPage" class="h-10"
                wire:loading.attr="readonly">
                <span wire:loading.remove wire:target="addPage" size="lg" class="flex items-center gap-2">
                    <x-icon name="o-plus" class="size-4" />
                    Add
                </span>
                <span wire:loading wire:target="addPage">
                    <x-icon name="o-arrow-path" class="size-4 animate-spin" />
                </span>
            </x-ui.button>

            <x-select wire:model="filter" :options="$filterOptions" option-value="value" option-label="label"
                placeholder="Filter" wire:change="$refresh"
                class="!h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                icon-right="o-funnel" />
            <x-search-input placeholder="Search..." class="max-w-md" wire:model.live.debounce.600ms="search" />
        </div>

    </div>

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

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
        @forelse ($surveyTemplates as $surveyTemplate)
            <div wire:loading.remove
                class="card bg-base-100 border border-primary/20 shadow h-44 transition duration-200 hover:shadow-md hover:border-primary/60 hover:bg-primary/5 hover:-translate-y-1 cursor-pointer">
                <div class="card-body p-4 md:p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm sm:text-base md:text-lg font-semibold text-base-content/90 leading-snug">
                                {{ $surveyTemplate->title }}
                            </h3>
                            <p class="text-xs md:text-sm text-base-content/60 mt-1">
                                {{ $surveyTemplate->description }}
                            </p>
                        </div>

                        @php
                            $status = $surveyTemplate->status ?? '';
                            $badgeClass = match ($status) {
                                'draft' => 'badge-warning',
                                'active' => 'badge-primary bg-primary/95',
                                default => 'badge-primary bg-primary/95',
                            };
                        @endphp

                        <x-badge :value="str($status)->title()" class="{{ $badgeClass }} badge-xs sm:badge-sm" />
                    </div>

                    @php
                        $level = $surveyTemplate->level ?? '';
                        $colorClasses = match ($level) {
                            1 => ['badge' => 'border-green-700 bg-green-50'],
                            2 => ['badge' => 'border-amber-700 bg-amber-50'],
                            3 => ['badge' => 'border-indigo-700 bg-indigo-50'],
                            default => ['badge' => 'border-primary bg-[#E4F3FF]'],
                        };
                    @endphp

                    <div class="mt-auto pt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if ($level)
                                <span
                                    class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] sm:text-[11px] border {{ $colorClasses['badge'] }}">
                                    Level {{ $level }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="p-6 border border-dashed rounded-lg text-center text-sm text-base-content/70 bg-base-100">
                    No survey templates available for you.
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4" wire:loading.remove>
        {{ $surveyTemplates->links('pagination::tailwind') }}
    </div>

</div>
