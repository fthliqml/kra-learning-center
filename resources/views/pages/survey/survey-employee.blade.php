<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-2xl sm:text-3xl lg:text-4xl font-bold text-center lg:text-start">
            Survey {{ $surveyLevel }}
        </h1>

        <div class="flex gap-3 flex-col w-full items-center justify-center lg:justify-end md:gap-2 md:flex-row">
            <x-select wire:model="filterStatus" :options="$filterOptions" option-value="value" option-label="label"
                placeholder="Filter" wire:change="$refresh"
                class="!h-10 focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                icon-right="o-funnel" />
            <x-search-input placeholder="Search..." class="max-w-md" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    <x-skeletons.survey-employee />

    {{-- Cards Grid for Employee --}}
    <div wire:loading.remove class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
        @forelse ($surveys as $survey)
            <div class="card bg-base-100 border border-primary/20 shadow h-48">
                <div class="card-body p-4 md:p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm sm:text-base md:text-lg font-semibold text-base-content/90 leading-snug">
                                {{ $survey->training_name ?? '-' }}
                            </h3>
                            <p class="text-xs md:text-sm text-base-content/60 mt-1">
                                {{ $survey->date ?? '-' }}
                            </p>
                        </div>
                        <x-badge :value="$survey->badge_label" class="{{ $survey->badge_class }} badge-xs sm:badge-sm" />
                    </div>

                    @php
                        $type = strtoupper($survey->training?->type ?? '');
                        $groupComp = $survey->training?->group_comp ?? null;
                        $colorClasses = match ($type) {
                            'IN' => ['badge' => 'border-green-700 !bg-green-100'],
                            'OUT' => ['badge' => 'border-amber-700 !bg-amber-100'],
                            'K-LEARN', 'KLEARN', 'KLEARNING' => ['badge' => 'border-indigo-700 !bg-indigo-100'],
                            default => ['badge' => 'border-primary bg-[#E4F3FF]'],
                        };
                    @endphp
                    <div class="mt-auto pt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if ($type)
                                <span
                                    class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] sm:text-[11px] bg-white/70 border {{ $colorClasses['badge'] }}">
                                    {{ $type }}
                                </span>
                            @endif
                            @if ($groupComp)
                                <span
                                    class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded text-[10px] sm:text-[11px] border border-primary bg-primary/10">
                                    {{ $groupComp }}
                                </span>
                            @endif
                        </div>
                        @if ($survey->badge_status === 'complete')
                            <a
                                href="{{ route('survey.preview', ['level' => $survey->level, 'surveyId' => $survey->id]) }}">
                                <x-button type="button" class="btn-xs sm:btn-sm border-primary/30 bg-primary/10"
                                    icon="o-eye" label="Preview Survey" />
                            </a>
                        @elseif ($survey->start_disabled)
                            <x-button type="button"
                                class="btn-xs sm:btn-sm border-primary/30 bg-gray-200 text-gray-400 cursor-not-allowed"
                                icon="o-play" label="Start Survey" disabled />
                        @else
                            <a
                                href="{{ route('survey.take', ['level' => $survey->level, 'surveyId' => $survey->id]) }}">
                                <x-button type="button" class="btn-xs sm:btn-sm border-primary/30 bg-success/10"
                                    icon="o-play" label="Start Survey" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="p-6 border border-dashed rounded-lg text-center text-sm text-base-content/70 bg-base-100">
                    No surveys available for you.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $surveys->links() }}
    </div>

</div>
