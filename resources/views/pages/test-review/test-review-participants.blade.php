<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-4 items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('test-review.index') }}" wire:navigate class="btn btn-ghost btn-sm btn-circle">
                <x-icon name="o-arrow-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-primary text-2xl font-bold">
                    {{ $training->name }}
                </h1>
                <p class="text-base-content/60 text-sm mt-0.5">
                    {{ $training->module?->title ?? 'No module' }}
                    <span class="mx-2">•</span>
                    <span
                        class="badge badge-sm border {{ match($training->type) {
                            'IN' => 'bg-green-100 text-green-700 border-green-300',
                            'OUT' => 'bg-amber-100 text-amber-700 border-amber-300',
                            'LMS' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
                            'BLENDED' => 'bg-purple-100 text-purple-700 border-purple-300',
                            default => 'bg-gray-100 text-gray-700 border-gray-300',
                        } }}">{{ $training->type }}</span>
                    <span class="mx-2">•</span>
                    @if ($hasPretest)
                        <span class="badge badge-xs badge-outline badge-primary">Pre-Test</span>
                    @endif
                    @if ($hasPosttest)
                        <span class="badge badge-xs badge-outline badge-secondary">Post-Test</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <div class="w-full sm:w-44">
                <x-select wire:model.live="filterStatus" :options="$statusOptions" option-value="value" option-label="label"
                    class="!h-10 w-full focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>
            <div class="w-full sm:w-64">
                <x-search-input placeholder="Search participant..." class="!w-full"
                    wire:model.live.debounce.400ms="search" />
            </div>
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-6"></div>

    {{-- Loading Skeleton --}}
    <x-skeletons.table :columns="5" :rows="5" targets="search,filterStatus" />

    {{-- Content --}}
    <div wire:loading.remove wire:target="search,filterStatus">
        @if ($participants->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-2">
                <div class="flex flex-col items-center justify-center py-12 px-4">
                    <x-icon name="o-users" class="w-16 h-16 text-gray-300 mb-3" />
                    <h3 class="text-base font-semibold text-gray-700 mb-1">No Participants</h3>
                    <p class="text-sm text-gray-500 text-center">
                        No participants found for this training.
                    </p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-base-300">
                <table class="table table-zebra">
                    <thead class="bg-base-200/50">
                        <tr>
                            <th class="text-sm font-semibold">Participant</th>
                            @if ($hasPretest)
                                <th class="text-sm font-semibold text-center">Pre-Test Status</th>
                            @endif
                            @if ($hasPosttest)
                                <th class="text-sm font-semibold text-center">Post-Test Status</th>
                            @endif
                            <th class="text-sm font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $assessment)
                            @if ($assessment)
                                <tr class="hover">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar placeholder">
                                                <div
                                                    class="bg-primary/10 text-primary rounded-full w-10 h-10 flex items-center justify-center">
                                                    <span class="text-sm font-bold">
                                                        {{ strtoupper(substr($assessment->employee?->name ?? 'U', 0, 2)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ $assessment->employee?->name ?? 'Unknown' }}
                                                </p>
                                                <p class="text-xs text-base-content/60">
                                                    {{ $assessment->employee?->identification_number ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    @if ($hasPretest)
                                        <td class="text-center">
                                            @if ($assessment->pretestStatus['status'] === 'under_review')
                                                <span class="badge badge-warning badge-sm">
                                                    <x-icon name="o-clock" class="size-3 mr-1" />
                                                    Need Review
                                                </span>
                                            @elseif ($assessment->pretestStatus['status'] === 'submitted')
                                                <div class="flex flex-col items-center">
                                                    <span
                                                        class="badge {{ $assessment->pretestStatus['isPassed'] ? 'badge-success' : 'badge-error' }} badge-sm">
                                                        {{ $assessment->pretestStatus['score'] ?? 0 }}%
                                                    </span>
                                                    <span class="text-xs text-base-content/50 mt-0.5">
                                                        {{ $assessment->pretestStatus['isPassed'] ? 'Passed' : 'Failed' }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-base-content/40 text-sm">Not taken</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if ($hasPosttest)
                                        <td class="text-center">
                                            @if ($assessment->posttestStatus['status'] === 'under_review')
                                                <div class="flex flex-col items-center">
                                                    <span class="badge badge-warning badge-sm">
                                                        <x-icon name="o-clock" class="size-3 mr-1" />
                                                        {{ $assessment->posttestStatus['needReviewCount'] }} Need
                                                        Review
                                                    </span>
                                                    @if ($assessment->posttestStatus['totalAttempts'] > 1)
                                                        <span class="text-xs text-base-content/50 mt-0.5">
                                                            {{ $assessment->posttestStatus['totalAttempts'] }} attempts
                                                        </span>
                                                    @endif
                                                </div>
                                            @elseif ($assessment->posttestStatus['status'] === 'submitted')
                                                <div class="flex flex-col items-center">
                                                    <span
                                                        class="badge {{ $assessment->posttestStatus['isPassed'] ? 'badge-success' : 'badge-error' }} badge-sm">
                                                        {{ $assessment->posttestStatus['score'] ?? 0 }}%
                                                    </span>
                                                    <span class="text-xs text-base-content/50 mt-0.5">
                                                        @if ($assessment->posttestStatus['totalAttempts'] > 1)
                                                            Best: {{ $assessment->posttestStatus['bestScore'] ?? 0 }}%
                                                            ({{ $assessment->posttestStatus['totalAttempts'] }}x)
                                                        @else
                                                            {{ $assessment->posttestStatus['isPassed'] ? 'Passed' : 'Failed' }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-base-content/40 text-sm">Not taken</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        @php
                                            $canReview =
                                                ($assessment->pretestStatus['status'] ?? null) !== null ||
                                                ($assessment->posttestStatus['status'] ?? null) !== null;
                                        @endphp
                                        @if ($canReview)
                                            <a href="{{ route('test-review.answers', ['training' => $training->id, 'user' => $assessment->employee_id]) }}"
                                                wire:navigate class="btn btn-sm btn-primary">
                                                <x-icon name="o-document-magnifying-glass" class="size-4" />
                                                Review
                                            </a>
                                        @else
                                            <span class="btn btn-sm btn-ghost btn-disabled">
                                                <x-icon name="o-document-magnifying-glass" class="size-4" />
                                                Review
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $participants->links() }}
            </div>
        @endif
    </div>
</div>
