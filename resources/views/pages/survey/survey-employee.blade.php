<div>
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-6 items-center justify-between">
        <h1 class="text-primary text-2xl sm:text-3xl font-bold text-center lg:text-start">
            Survey {{ $surveyLevel }}
        </h1>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <div class="w-full sm:w-48">
                <x-select wire:model="filterStatus" :options="$filterOptions" option-value="value" option-label="label"
                    placeholder="Filter Status" wire:change="$refresh"
                    class="!h-10 w-full focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>
            <x-search-input placeholder="Search..." class="max-w-72" wire:model.live.debounce.600ms="search" />
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-6"></div>

    <x-skeletons.survey-employee />

    {{-- Cards Grid for Employee --}}
    <div wire:loading.remove class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
        @forelse ($surveys as $survey)
            <div
                class="rounded-2xl bg-base-100 border border-base-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition duration-200 min-h-56">
                <div class="p-4 md:p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3
                                class="text-sm sm:text-base md:text-lg font-semibold text-base-content leading-snug truncate">
                                {{ $survey->training_name ?? '-' }}
                            </h3>
                            <div class="mt-1 flex items-center gap-2 text-xs md:text-sm text-base-content/60">
                                <x-icon name="o-calendar-days" class="size-4 opacity-70" />
                                <span class="truncate">{{ $survey->date ?? '-' }}</span>
                            </div>
                        </div>
                        <x-badge :value="$survey->badge_label"
                            class="{{ $survey->badge_class }} badge-xs sm:badge-sm whitespace-nowrap" />
                    </div>

                    @if ((int) ($survey->level ?? 0) === 3)
                        <div class="mt-4 rounded-xl border border-base-200 bg-base-200/40 p-3 space-y-2">
                            <div class="grid grid-cols-[70px_1fr] gap-2 items-start text-xs md:text-sm">
                                <div class="text-base-content/60 font-medium">Name</div>
                                <div class="text-base-content/80 leading-snug break-words">
                                    {{ $survey->target_employees_label ?? '-' }}
                                </div>
                            </div>
                            <div class="grid grid-cols-[70px_1fr] gap-2 items-center text-xs md:text-sm">
                                <div class="text-base-content/60 font-medium">Ready at</div>
                                <div class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg border border-base-300 bg-base-100 text-base-content/80">
                                        <x-icon name="o-clock" class="size-4 opacity-70" />
                                        {{ $survey->can_start_label ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php
                        $type = strtoupper($survey->training?->type ?? '');
                        $groupComp = $survey->training?->group_comp ?? null;
                        $colorClasses = match ($type) {
                            'IN' => ['badge' => 'border-green-700 !bg-green-100'],
                            'OUT' => ['badge' => 'border-amber-700 !bg-amber-100'],
                            'LMS' => ['badge' => 'border-indigo-700 !bg-indigo-100'],
                            default => ['badge' => 'border-primary bg-[#E4F3FF]'],
                        };
                    @endphp
                    <div class="mt-auto pt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if ($type)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] sm:text-[11px] bg-base-100/70 border {{ $colorClasses['badge'] }} whitespace-nowrap">
                                    {{ $type }}
                                </span>
                            @endif
                            @if ($groupComp)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] sm:text-[11px] border border-primary/30 bg-primary/10 whitespace-nowrap">
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
                                class="btn-xs sm:btn-sm border-base-300 bg-base-200 text-base-content/40 cursor-not-allowed"
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
