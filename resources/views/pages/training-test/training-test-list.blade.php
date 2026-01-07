<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-4 items-center justify-between">
        <div>
            <h1 class="text-primary text-2xl font-bold text-center lg:text-start">
                Training Test
            </h1>
            <p class="text-base-content/60 text-xs mt-0.5">
                Complete your pretest and posttest for assigned in-house trainings
            </p>
        </div>

        <div class="flex gap-2 w-full lg:w-auto items-center justify-center lg:justify-end">
            <x-search-input placeholder="Search training..." class="max-w-64" wire:model.live.debounce.400ms="search" />
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-4"></div>

    {{-- Loading Skeleton --}}
    <x-skeletons.table :columns="4" :rows="5" targets="search" />

    {{-- Content --}}
    <div wire:loading.remove wire:target="search">
        @if ($trainings->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-2">
                <div class="flex flex-col items-center justify-center py-12 px-4">
                    <x-icon name="o-clipboard-document-check" class="w-16 h-16 text-gray-300 mb-3" />
                    <h3 class="text-base font-semibold text-gray-700 mb-1">No Training Tests Available</h3>
                    <p class="text-xs text-gray-500 text-center">
                        You don't have any in-house training tests to complete at the moment.
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($trainings as $training)
                    <div
                        class="bg-base-100 rounded-xl border border-base-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        {{-- Header --}}
                        <div class="bg-gradient-to-r from-primary/12 to-primary/7 px-5 py-3 border-b border-base-200">
                            <h3 class="font-bold text-lg text-base-content truncate" title="{{ $training->name }}">
                                {{ $training->name }}
                            </h3>
                            <p class="text-sm text-base-content/60 mt-0.5 truncate">
                                {{ $training->module?->title ?? 'No module assigned' }}
                            </p>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 space-y-4">
                            {{-- Training Info --}}
                            <div class="flex items-center gap-4 text-xs text-base-content/70">
                                <div class="flex items-center gap-1.5">
                                    <x-icon name="o-calendar" class="size-4" />
                                    <span>{{ $training->start_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <x-icon name="o-tag" class="size-4" />
                                    <span class="badge badge-sm badge-ghost">{{ $training->type }}</span>
                                </div>
                            </div>

                            {{-- Test Status --}}
                            <div class="space-y-3">
                                {{-- Pretest --}}
                                <div class="flex items-center justify-between p-3 rounded-lg bg-base-200/80">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs
                                            @if ($training->testStatus['pretest'] === 'completed') bg-success/20 text-success
                                            @elseif($training->testStatus['pretest'] === 'under_review') bg-warning/20 text-warning
                                            @elseif($training->testStatus['pretest'] === 'available') bg-primary/20 text-primary
                                            @else bg-base-300 text-base-content/40 @endif">
                                            @if ($training->testStatus['pretest'] === 'completed')
                                                <x-icon name="o-check" class="size-4" />
                                            @elseif($training->testStatus['pretest'] === 'under_review')
                                                <x-icon name="o-clock" class="size-4" />
                                            @elseif($training->testStatus['pretest'] === 'available')
                                                <x-icon name="o-play" class="size-4" />
                                            @else
                                                <x-icon name="o-minus" class="size-4" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Pretest</p>
                                            @if ($training->testStatus['pretest'] === 'completed')
                                                <p class="text-xs text-success">Score:
                                                    {{ $training->testStatus['pretestScore'] ?? 0 }}%</p>
                                            @elseif($training->testStatus['pretest'] === 'under_review')
                                                <p class="text-xs text-warning">Under review</p>
                                            @elseif($training->testStatus['pretest'] === 'available')
                                                <p class="text-xs text-base-content/60">Ready to take</p>
                                            @else
                                                <p class="text-xs text-base-content/40">Not available</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($training->testStatus['pretest'] === 'available')
                                        <a href="{{ route('training-test.take', ['training' => $training->id, 'type' => 'pretest']) }}"
                                            wire:navigate class="btn btn-sm btn-primary">
                                            Start
                                        </a>
                                    @elseif($training->testStatus['pretest'] === 'completed')
                                        <span class="badge badge-success badge-sm">Done</span>
                                    @elseif($training->testStatus['pretest'] === 'under_review')
                                        <span class="badge badge-warning badge-sm">Review</span>
                                    @endif
                                </div>

                                {{-- Posttest --}}
                                <div class="flex items-center justify-between p-3 rounded-lg bg-base-200/80">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs
                                            @if ($training->testStatus['posttest'] === 'completed') bg-success/20 text-success
                                            @elseif($training->testStatus['posttest'] === 'under_review') bg-warning/20 text-warning
                                            @elseif($training->testStatus['posttest'] === 'available') bg-primary/20 text-primary
                                            @elseif($training->testStatus['posttest'] === 'retake') bg-error/20 text-error
                                            @elseif($training->testStatus['posttest'] === 'failed') bg-error/20 text-error
                                            @elseif($training->testStatus['posttest'] === 'locked') bg-base-300 text-base-content/50
                                            @else bg-base-300 text-base-content/40 @endif">
                                            @if ($training->testStatus['posttest'] === 'completed')
                                                <x-icon name="o-check" class="size-4" />
                                            @elseif($training->testStatus['posttest'] === 'under_review')
                                                <x-icon name="o-clock" class="size-4" />
                                            @elseif($training->testStatus['posttest'] === 'available')
                                                <x-icon name="o-play" class="size-4" />
                                            @elseif($training->testStatus['posttest'] === 'retake')
                                                <x-icon name="o-arrow-path" class="size-4" />
                                            @elseif($training->testStatus['posttest'] === 'failed')
                                                <x-icon name="o-x-mark" class="size-4" />
                                            @elseif($training->testStatus['posttest'] === 'locked')
                                                <x-icon name="o-lock-closed" class="size-4" />
                                            @else
                                                <x-icon name="o-minus" class="size-4" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Posttest</p>
                                            @if ($training->testStatus['posttest'] === 'completed')
                                                <p class="text-xs text-success">
                                                    Passed: {{ $training->testStatus['posttestScore'] ?? 0 }}%
                                                    @if ($training->testStatus['posttestAttempts'] > 1)
                                                        <span
                                                            class="opacity-70">({{ $training->testStatus['posttestAttempts'] }}
                                                            attempts)</span>
                                                    @endif
                                                </p>
                                            @elseif($training->testStatus['posttest'] === 'under_review')
                                                <p class="text-xs text-warning">Under review</p>
                                            @elseif($training->testStatus['posttest'] === 'available')
                                                <p class="text-xs text-base-content/60">Ready to take</p>
                                            @elseif($training->testStatus['posttest'] === 'retake')
                                                <p class="text-xs text-error">
                                                    Failed: {{ $training->testStatus['posttestScore'] ?? 0 }}%
                                                    @if ($training->testStatus['posttestMaxAttempts'])
                                                        <span
                                                            class="opacity-70">({{ $training->testStatus['posttestAttempts'] }}/{{ $training->testStatus['posttestMaxAttempts'] }}
                                                            attempts)</span>
                                                    @else
                                                        <span
                                                            class="opacity-70">({{ $training->testStatus['posttestAttempts'] }}
                                                            attempts)</span>
                                                    @endif
                                                </p>
                                            @elseif($training->testStatus['posttest'] === 'failed')
                                                <p class="text-xs text-error">
                                                    Failed: {{ $training->testStatus['posttestScore'] ?? 0 }}%
                                                    <span class="opacity-70">(Max attempts reached)</span>
                                                </p>
                                            @elseif($training->testStatus['posttest'] === 'locked')
                                                <p class="text-xs text-base-content/50">Complete pretest first</p>
                                            @else
                                                <p class="text-xs text-base-content/40">Not available</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($training->testStatus['posttest'] === 'available')
                                        <a href="{{ route('training-test.take', ['training' => $training->id, 'type' => 'posttest']) }}"
                                            wire:navigate class="btn btn-sm btn-primary">
                                            Start
                                        </a>
                                    @elseif($training->testStatus['posttest'] === 'retake')
                                        <a href="{{ route('training-test.take', ['training' => $training->id, 'type' => 'posttest']) }}"
                                            wire:navigate class="btn btn-sm btn-error btn-outline">
                                            Retake
                                        </a>
                                    @elseif($training->testStatus['posttest'] === 'completed')
                                        <span class="badge badge-success badge-sm">Passed</span>
                                    @elseif($training->testStatus['posttest'] === 'under_review')
                                        <span class="badge badge-warning badge-sm">Review</span>
                                    @elseif($training->testStatus['posttest'] === 'failed')
                                        <span class="badge badge-error badge-sm">Failed</span>
                                    @elseif($training->testStatus['posttest'] === 'locked')
                                        <span class="badge badge-ghost badge-sm">Locked</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $trainings->links() }}
            </div>
        @endif
    </div>
</div>
