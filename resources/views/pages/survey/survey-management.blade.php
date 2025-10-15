<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9
                grid-cols-1 lg:grid-cols-2 items-center">
        <h1 class="text-primary text-4xl font-bold text-center lg:text-start">
            Survey {{ $surveyLevel }}
        </h1>
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
                    // Map to badge classes (no variant prop)
                    $badgeClass = match ($status) {
                        'draft' => 'badge-warning',
                        'completed' => 'badge-primary bg-primary/95',
                        'incomplete' => ' badge primary badge-soft',
                    };
                @endphp
                <x-badge :value="str($status)->title()" :class="$badgeClass" />
            @endscope

            @scope('cell_action', $survey)
                <div class="flex gap-2 justify-center">
                    <x-button icon="o-pencil-square" class="btn-circle btn-ghost bg-tetriary p-2" spinner
                        wire:click="openEditModal({{ $survey->id }})" />
                </div>
            @endscope
        </x-table>
    </div>
</div>
