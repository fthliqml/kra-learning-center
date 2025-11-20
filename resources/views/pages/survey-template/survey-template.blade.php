<div>
    <div class="w-full flex gap-5 mb-5 lg:mb-9 items-center">
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

    <x-skeletons.survey-template-skeleton />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
        @forelse ($surveyTemplates as $surveyTemplate)
            <a wire:loading.remove
                href="{{ route('survey-template.edit', ['level' => $surveyTemplate->level, 'surveyId' => $surveyTemplate->id]) }}"
                class="card bg-base-100 border border-primary/20 shadow h-44 transition duration-200 hover:shadow-md hover:border-primary/60 hover:bg-primary/5 hover:-translate-y-1 cursor-pointer block">
                <div class="card-body p-4 md:p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm sm:text-base md:text-lg font-semibold text-base-content/90 leading-snug truncate"
                                title="{{ $surveyTemplate->title }}">
                                {{ $surveyTemplate->title }}
                            </h3>
                            <p class="text-[11px] md:text-xs text-base-content/60 mt-1 break-words"
                                style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;"
                                title="{{ $surveyTemplate->description }}">
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
            </a>
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
