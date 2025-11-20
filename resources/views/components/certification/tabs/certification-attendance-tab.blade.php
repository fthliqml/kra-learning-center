<div class="space-y-4">
    <div class="flex justify-between items-start sm:items-center gap-3 sm:gap-4 w-full">
        <h2 class="text-lg font-semibold">Attendance (Session {{ $sessionId }})</h2>
        @php $isLeader = auth()->user()?->hasRole('leader'); @endphp
        @if(!$readOnly && !$isLeader)
            <button wire:click="save" wire:loading.attr="disabled"
                class="btn btn-primary btn-sm gap-2"
                title="Save attendance changes">
                <span wire:loading.remove>Save Attendance</span>
                <span wire:loading class="loading loading-spinner loading-xs"></span>
                <span wire:loading>Saving</span>
            </button>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 shadow">
        <div class="p-2 overflow-x-auto">
            <div class="max-h-[400px] overflow-y-auto thin-scrollbar">
                    <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr class="text-xs uppercase tracking-wide text-gray-600">
                            <th class="py-3 px-2 text-center w-12 border-b border-gray-200">No</th>
                            <th class="py-3 px-3 text-left min-w-[180px] border-b border-gray-200">Name</th>
                            <th class="py-3 px-2 text-center w-40 border-b border-gray-200">Status</th>
                            <th class="py-3 px-2 text-center min-w-[220px] border-b border-gray-200">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $idx => $p)
                            @php $row = $attendance[$p['id']] ?? []; @endphp
                            <tr class="even:bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="py-2.5 px-2 text-center text-xs font-medium">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-medium">
                                    <div class="truncate" title="{{ $p['name'] ?? '—' }}">{{ $p['name'] ?? '—' }}</div>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @php $statusVal = $row['status'] ?? null; @endphp
                                    @if($readOnly && $isLeader)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border
                                            @switch($statusVal)
                                                @case('present') bg-green-50 text-green-700 border-green-300 @break
                                                @case('absent') bg-red-50 text-red-600 border-red-300 @break
                                                @default bg-amber-50 text-amber-600 border-amber-300
                                            @endswitch">
                                            {{ $statusVal ? ucfirst($statusVal) : 'Pending' }}
                                        </span>
                                    @else
                                        <x-select :options="[
                                            ['value' => 'present', 'label' => 'Present'],
                                            ['value' => 'absent', 'label' => 'Absent'],
                                        ]" option-value="value" option-label="label"
                                            wire:model.defer="attendance.{{ $p['id'] }}.status" placeholder="Pending"
                                            class="!w-full !max-w-[9.5rem] !min-h-0 !h-7 [&_input]:!py-0 [&_.select-trigger]:!py-0 text-xs focus-within:outline-0" />
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @if($readOnly && $isLeader)
                                        @php $text = trim($row['remark'] ?? '') === '' ? '—' : $row['remark']; @endphp
                                        <div class="flex justify-center">
                                            <input type="text" value="{{ $text }}" disabled
                                                class="w-full max-w-[15rem] rounded-md border text-xs px-2 py-1 bg-gray-50 border-gray-200 text-gray-600 italic" />
                                        </div>
                                    @else
                                        <div class="flex justify-center">
                                            <input type="text" wire:model.defer="attendance.{{ $p['id'] }}.remark"
                                                placeholder="Optional remark"
                                                class="w-full max-w-[15rem] rounded-md border border-gray-300 text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none" />
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-sm text-gray-500">No participants found for this certification.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-4 py-2 border-t border-gray-200 flex flex-col sm:flex-row gap-2 sm:items-center justify-between bg-gray-50 text-[11px] text-gray-600">
            <div class="font-medium">Total Participants: {{ count($participants) }}</div>
            @php
                $present = collect($attendance)->where('status','present')->count();
                $absent = collect($attendance)->where('status','absent')->count();
                $pending = max(0, count($participants) - $present - $absent);
            @endphp
            <div class="flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>Present: {{ $present }}</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Absent: {{ $absent }}</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Pending: {{ $pending }}</span>
            </div>
        </div>
    </div>
</div>

