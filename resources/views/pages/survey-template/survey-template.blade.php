<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Survey Template
        </h1>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
        @forelse ($surveyTemplates as $surveyTemplate)
            <div
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


</div>
