<div class="space-y-4">
    <!-- Header -->
    <div class="flex justify-between items-start sm:items-center gap-3 sm:gap-4 w-full">
        <h2 class="text-lg font-semibold">Attendance (Day {{ $dayNumber }})</h2>
        <button @disabled($readOnly) wire:click="save" wire:loading.attr="disabled"
            class="btn btn-primary btn-sm gap-2 {{ $readOnly ? 'btn-disabled opacity-60 cursor-not-allowed' : '' }}"
            title="{{ $readOnly ? 'Training is closed' : '' }}">
            <span wire:loading.remove>Save Attendance</span>
            <span wire:loading class="loading loading-spinner loading-xs"></span>
            <span wire:loading>Saving</span>
        </button>
    </div>

    <!-- Styled Container -->
    <div class="rounded-lg border border-gray-200 shadow">
        <div class="p-2 overflow-x-auto">
            <!-- Scrollable body with max height -->
            <div class="max-h-[400px] overflow-y-auto thin-scrollbar">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr class="text-xs uppercase tracking-wide text-gray-600">
                            <th class="py-3 px-2 text-center w-12 border-b border-gray-200">No</th>
                            <th class="py-3 px-2 text-center w-28 border-b border-gray-200">NRP</th>
                            <th class="py-3 px-3 text-left min-w-[180px] border-b border-gray-200">Name</th>
                            <th class="py-3 px-2 text-center w-40 border-b border-gray-200">Section</th>
                            <th class="py-3 px-2 text-center w-44 border-b border-gray-200">Status</th>
                            <th class="py-3 px-2 text-center min-w-[240px] border-b border-gray-200">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $idx => $emp)
                            @php $row = $attendance[$emp['id']] ?? []; @endphp
                            <tr class="even:bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="py-2.5 px-2 text-center text-xs font-medium">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-2 text-center font-mono text-[11px]">{{ $emp['NRP'] ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3 font-medium">
                                    <div class="truncate" title="{{ $emp['name'] }}">{{ $emp['name'] }}</div>
                                </td>
                                <td class="py-2.5 px-2 text-center text-xs">{{ $emp['section'] ?? '—' }}</td>
                                <td class="py-2.5 px-2 text-center">
                                    <x-select :options="[
                                        ['value' => 'pending', 'label' => 'Pending'],
                                        ['value' => 'present', 'label' => 'Present'],
                                        ['value' => 'absent', 'label' => 'Absent'],
                                    ]" option-value="value" option-label="label"
                                        wire:model.defer="attendance.{{ $emp['id'] }}.status" :placeholder="null"
                                        :disabled="$readOnly"
                                        class="!w-full !max-w-[9.5rem] !min-h-0 !h-7 [&_input]:!py-0 [&_.select-trigger]:!py-0 text-xs focus-within:outline-0 {{ $readOnly ? 'opacity-60 cursor-not-allowed' : '' }}" />
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <input type="text" wire:model.defer="attendance.{{ $emp['id'] }}.remark"
                                        @disabled($readOnly) placeholder="Optional remark"
                                        class="w-full max-w-[15rem] rounded-md border border-gray-300 text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none {{ $readOnly ? 'opacity-60 cursor-not-allowed bg-gray-50' : '' }}" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">No employees found
                                    for this training.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Footer Summary -->
        <div
            class="px-4 py-2 border-t border-gray-200 flex flex-col sm:flex-row gap-2 sm:items-center justify-between bg-gray-50 text-[11px] text-gray-600">
            <div class="font-medium">Total Participants: {{ count($employees) }}</div>
            <div class="flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-1"><span
                        class="w-2.5 h-2.5 rounded-full bg-green-500"></span>Present:
                    {{ collect($attendance)->where('status', 'present')->count() }}</span>
                <span class="inline-flex items-center gap-1"><span
                        class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Absent:
                    {{ collect($attendance)->where('status', 'absent')->count() }}</span>
                <span class="inline-flex items-center gap-1"><span
                        class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Pending:
                    {{ collect($attendance)->where('status', 'pending')->count() }}</span>
            </div>
        </div>
    </div>
</div>

<style>
    .thin-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .thin-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .thin-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(120, 120, 120, .28);
        border-radius: 3px;
    }

    .thin-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(120, 120, 120, .45);
    }
</style>
