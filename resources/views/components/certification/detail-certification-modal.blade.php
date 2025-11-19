<div>
    @if (!empty($selected))
        <x-modal wire:model="modal" icon='o-document-text' title="Certification Details"
            subtitle="Information about certification session" separator
            box-class="w-[calc(100vw-2rem)] max-w-full sm:max-w-4xl h-fit">
            <div class="px-1 pt-2 mb-4 border-b border-gray-200 flex items-end justify-between gap-4 text-sm font-medium">
                <div class="flex gap-6">
                    <button type="button" class="pb-3 relative text-primary">Information
                        <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                    </button>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    @php
                        $type = strtoupper($selected['type'] ?? '');
                        switch ($type) {
                            case 'THEORY':
                                $badge = 'bg-yellow-100 text-yellow-700 border border-yellow-300';
                                break;
                            case 'PRACTICAL':
                            case 'PRACTICE':
                                $badge = 'bg-indigo-100 text-indigo-700 border border-indigo-300';
                                break;
                            default:
                                $badge = 'bg-blue-100 text-blue-700 border border-blue-300';
                        }
                    @endphp
                    @if ($type)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold tracking-wide {{ $badge }}">{{ $type }}</span>
                    @endif
                    @if (!empty($sessionOptions))
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Session</span>
                            <x-select wire:model="selectedSessionId" wire:change="selectSession($event.detail.value)" :options="$sessionOptions" option-label="name" option-value="id" class="w-56 !h-8 !text-sm" />
                        </div>
                    @endif
                </div>
            </div>

            @php $mod = $selected['module'] ?? []; @endphp
            <div class="space-y-6">
                <!-- Title -->
                <div class="p-4 border rounded-md">
                    <div class="flex items-start gap-2">
                        <x-icon name="o-academic-cap" class="w-4 h-4 text-primary/70 mt-0.5" />
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Title</p>
                            <p class="font-semibold text-gray-800 break-words mt-1">{{ $selected['title'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="p-4 border rounded-md">
                    <div class="flex items-start gap-2">
                        <x-icon name="o-calendar" class="w-4 h-4 text-primary/70 mt-0.5" />
                        <div class="flex-1">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Date</p>
                            <p class="font-semibold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($selected['date'])->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Module -->
                    <div class="p-4 border rounded-md">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-rectangle-group" class="w-4 h-4 text-primary/70 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Module</p>
                                <p class="font-semibold text-gray-800 mt-1">{{ $mod['module_title'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Group -->
                    <div class="p-4 border rounded-md">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-squares-2x2" class="w-4 h-4 text-primary/70 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Group</p>
                                <p class="font-semibold text-gray-800 mt-1">{{ $mod['group_certification'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Level -->
                    <div class="p-4 border rounded-md">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-chart-bar" class="w-4 h-4 text-primary/70 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Level</p>
                                <p class="font-semibold text-gray-800 mt-1">{{ $mod['level'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Location -->
                    <div class="p-4 border rounded-md">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-map-pin" class="w-4 h-4 text-primary/70 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Location</p>
                                <p class="font-semibold text-gray-800 mt-1">{{ $selected['location'] ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Time -->
                    <div class="p-4 border rounded-md">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-clock" class="w-4 h-4 text-primary/70 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Time</p>
                                <p class="font-semibold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($selected['start_time'])->format('H:i') }} - {{ \Carbon\Carbon::parse($selected['end_time'])->format('H:i') }}<br><span class="text-xs text-gray-500">WITA</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-6">
                <x-button wire:click="closeModal" class="btn bg-white hover:bg-gray-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
            </div>
        </x-modal>
    @endif
</div>
