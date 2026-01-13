<div>
    <div class="w-full flex flex-col mb-5 lg:mb-9 gap-2">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Survey Management
        </h1>
        <span class="badge badge-primary badge-soft">Level {{ $surveyLevel }}</span>
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-gray-200 shadow-all p-2 overflow-x-auto">
        <x-table :headers="$headers" :rows="$surveys" striped class="[&>tbody>tr>td]:py-2 [&>thead>tr>th]:!py-3"
            with-pagination>
            @scope('cell_no', $survey)
                {{ $survey->no ?? $loop->iteration }}
            @endscope

            @scope('cell_status', $survey)
                @php
                    $status = $survey->status ?? 'incomplete';

                    $label = match ($status) {
                        'draft' => 'Draft',
                        'completed' => 'Completed',
                        // NOTE: 'incomplete' means survey is published/in progress (not all participants completed yet)
                        'incomplete' => 'In Progress',
                        default => str($status)->title(),
                    };
                    // Map to badge classes (no variant prop)
                    $badgeClass = match ($status) {
                        'draft' => 'badge-warning',
                        'completed' => 'badge-primary bg-primary/95',
                        'incomplete' => 'badge-primary badge-soft',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-badge :value="$label" :class="$badgeClass . ' whitespace-nowrap'" />
            @endscope

            @scope('cell_action', $survey)
                <div class="flex gap-2 justify-center">
                    <a href="{{ route('survey.edit', ['level' => $this->surveyLevel, 'surveyId' => $survey->id]) }}">
                        <x-button icon="o-pencil-square" class="btn-circle btn-ghost bg-tetriary p-2" />
                    </a>
                </div>
            @endscope
        </x-table>
    </div>
</div>
