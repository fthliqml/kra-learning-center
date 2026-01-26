<div>
    {{-- Header --}}
    <div class="w-full flex flex-col lg:flex-row gap-4 mb-4 items-center justify-between">
        <div>
            <h1 class="text-primary text-2xl font-bold text-center lg:text-start">
                Test Review
            </h1>
            <p class="text-base-content/60 text-sm mt-0.5">
                Review and grade participant test submissions
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <div class="w-full sm:w-36">
                <x-select wire:model.live="filterType" :options="$typeOptions" option-value="value" option-label="label"
                    class="!h-10 w-full focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-funnel" />
            </div>
            <div class="w-full sm:w-40">
                <x-select wire:model.live="filterStatus" :options="$statusOptions" option-value="value" option-label="label"
                    class="!h-10 w-full focus-within:border-0 hover:outline-1 focus-within:outline-1 cursor-pointer [&_svg]:!opacity-100"
                    icon-right="o-adjustments-horizontal" />
            </div>
            <div class="w-full sm:w-64">
                <x-search-input placeholder="Search training..." class="!w-full"
                    wire:model.live.debounce.400ms="search" />
            </div>
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-b-2 border-base-300 mb-6"></div>

    {{-- Loading Skeleton --}}
    <x-skeletons.table :columns="5" :rows="5" targets="search,filterType,filterStatus" />

    {{-- Content --}}
    <div wire:loading.remove wire:target="search,filterType,filterStatus">
        @if ($trainings->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-2">
                <div class="flex flex-col items-center justify-center py-12 px-4">
                    <x-icon name="o-clipboard-document-check" class="w-16 h-16 text-gray-300 mb-3" />
                    <h3 class="text-base font-semibold text-gray-700 mb-1">No Trainings to Review</h3>
                    <p class="text-sm text-gray-500 text-center">
                        You don't have any training tests to review at the moment.
                    </p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-base-300">
                <table class="table table-zebra">
                    <thead class="bg-base-200/50">
                        <tr>
                            <th class="text-sm font-semibold">Training</th>
                            <th class="text-sm font-semibold">Type</th>
                            <th class="text-sm font-semibold">Status</th>
                            <th class="text-sm font-semibold text-center">Tests</th>
                            <th class="text-sm font-semibold text-center">Participants</th>
                            <th class="text-sm font-semibold text-center">Need Review</th>
                            <th class="text-sm font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trainings as $training)
                            <tr class="hover">
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-base-content">{{ $training->name }}</span>
                                        <span class="text-xs text-base-content/60">
                                            {{ $training->module?->title ?? 'No module' }}
                                        </span>
                                        @if ($training->start_date)
                                            <span class="text-xs text-base-content/50 mt-0.5">
                                                {{ $training->start_date->format('d M Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span
                                        class="badge badge-sm border {{ match($training->type) {
                                            'IN' => 'bg-green-100 text-green-700 border-green-300',
                                            'OUT' => 'bg-amber-100 text-amber-700 border-amber-300',
                                            'LMS' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
                                            'BLENDED' => 'bg-purple-100 text-purple-700 border-purple-300',
                                            default => 'bg-gray-100 text-gray-700 border-gray-300',
                                        } }}">
                                        {{ match($training->type) {
                                            'IN' => 'In-House',
                                            'OUT' => 'Out-House',
                                            'LMS' => 'LMS',
                                            'BLENDED' => 'Blended',
                                            default => $training->type,
                                        } }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @php
                                        $statusBadge = match ($training->status) {
                                            'in_progress' => 'badge-primary',
                                            'done' => 'badge-success',
                                            'approved' => 'badge-info',
                                            'cancelled' => 'badge-error',
                                            'rejected' => 'badge-warning',
                                            default => 'badge-ghost',
                                        };
                                    @endphp
                                    <span class="badge badge-sm {{ $statusBadge }}">
                                        {{ ucfirst(str_replace('_', ' ', $training->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($training->reviewStats['hasPretest'])
                                            <span class="badge badge-xs badge-outline badge-primary"
                                                title="Has Pre-Test">Pre</span>
                                        @endif
                                        @if ($training->reviewStats['hasPosttest'])
                                            <span class="badge badge-xs badge-outline badge-secondary"
                                                title="Has Post-Test">Post</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="font-medium">{{ $training->reviewStats['totalParticipants'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($training->reviewStats['needReview'] > 0)
                                        <span class="badge badge-warning badge-sm">
                                            {{ $training->reviewStats['needReview'] }} pending
                                        </span>
                                    @else
                                        <span class="text-success text-sm">
                                            <x-icon name="o-check-circle" class="size-4 inline" /> All reviewed
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('test-review.participants', $training->id) }}" wire:navigate
                                        class="btn btn-sm btn-primary">
                                        <x-icon name="o-eye" class="size-4" />
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $trainings->links() }}
            </div>
        @endif
    </div>
</div>
