<div>
    @if ($selectedEvent)
        <x-modal wire:model="modal" icon='o-document-text' title="Training Details"
            subtitle="Information about training details" separator
            box-class="w-[calc(100vw-2rem)] max-w-full sm:max-w-4xl h-fit">
            @if (in_array(strtolower($selectedEvent['status'] ?? ''), ['approved', 'rejected', 'done']))
                <div
                    class="mt-3 mb-3 p-3 rounded-lg {{ strtolower($selectedEvent['status'] ?? '') === 'approved' ? 'bg-green-50 border border-green-200' : (strtolower($selectedEvent['status'] ?? '') === 'rejected' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200') }}">
                    <p
                        class="text-sm {{ strtolower($selectedEvent['status'] ?? '') === 'approved' ? 'text-green-700' : (strtolower($selectedEvent['status'] ?? '') === 'rejected' ? 'text-red-700' : 'text-blue-700') }}">
                        <strong>Training {{ ucfirst(strtolower($selectedEvent['status'] ?? '')) }}:</strong>
                        @if (strtolower($selectedEvent['status'] ?? '') === 'approved')
                            Certificates are available.
                        @elseif(strtolower($selectedEvent['status'] ?? '') === 'rejected')
                            This training has been rejected.
                        @else
                            Waiting for approval.
                        @endif
                    </p>
                </div>
            @endif
            {{-- Tabs navigation --}}
            <div
                class="px-1 pt-2 mb-4 border-b border-gray-200 flex items-end justify-between gap-4 text-sm font-medium">
                <div class="flex gap-6">
                    @php
                        $isLms = strtoupper($selectedEvent['type'] ?? '') === 'LMS';
                    @endphp
                    <button type="button" wire:click="$set('activeTab','information')"
                        class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'information' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                        Information
                        @if ($activeTab === 'information')
                            <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                        @endif
                    </button>
                    @anyrole('admin', 'instructor')
                        @if (!$isLms)
                            <button type="button" wire:click="$set('activeTab','attendance')"
                                class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'attendance' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                                Attendance
                                @if ($activeTab === 'attendance')
                                    <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                                @endif
                            </button>
                        @endif
                        <button type="button" wire:click="$set('activeTab','close-training')"
                            class="pb-3 relative cursor-pointer focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 outline-none transition {{ $activeTab === 'close-training' ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }}">
                            Close Training
                            @if ($activeTab === 'close-training')
                                <span class="absolute left-0 -bottom-[1px] h-[2px] w-full bg-primary rounded"></span>
                            @endif
                        </button>
                    @endanyrole
                </div>
                <div class="flex items-center gap-3 pb-1">
                    @php
                        $type = strtoupper($selectedEvent['type'] ?? '');
                        switch ($type) {
                            case 'IN':
                                $badge = 'bg-green-100 text-green-700 border border-green-300';
                                break;
                            case 'OUT':
                                $badge = 'bg-amber-100 text-amber-700 border border-amber-300';
                                break;
                            case 'LMS':
                                $badge = 'bg-indigo-100 text-indigo-700 border border-indigo-300';
                                break;
                            case 'BLENDED':
                                $badge = 'bg-purple-100 text-purple-700 border border-purple-300';
                                break;
                            default:
                                $badge = 'bg-blue-100 text-blue-700 border border-blue-300';
                        }
                    @endphp
                    @if ($type)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold tracking-wide {{ $badge }}">
                            {{ $type }}
                        </span>
                    @endif
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Day</span>
                        <x-select wire:model="dayNumber" :options="$trainingDates" option-label="name" option-value="id"
                            wire:change="$refresh" class="w-40 !h-8 !text-sm focus-within:outline-none" />
                    </div>
                </div>
            </div>

            @if ($activeTab === 'information')
                {{-- Tests Section for Employees --}}
                @if($isEmployee)
                    <div class="mb-6 bg-base-200 rounded-xl border border-base-300 overflow-hidden">
                        <div class="px-4 py-3 bg-base-300 border-b border-base-300 flex items-center justify-between">
                            <h3 class="font-semibold text-sm">Training Tests</h3>
                            <span class="text-xs text-base-content/60">Complete in order</span>
                        </div>
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Pre-Test --}}
                            <div class="flex items-center justify-between p-3 rounded-lg bg-base-100 border border-base-200 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs
                                        @if (($testStatus['pretest'] ?? '') === 'completed') bg-success/20 text-success
                                        @elseif(($testStatus['pretest'] ?? '') === 'under_review') bg-warning/20 text-warning
                                        @elseif(($testStatus['pretest'] ?? '') === 'available') bg-primary/20 text-primary
                                        @else bg-base-300 text-base-content/40 @endif">
                                        @if (($testStatus['pretest'] ?? '') === 'completed')
                                            <x-icon name="o-check" class="size-4" />
                                        @elseif(($testStatus['pretest'] ?? '') === 'under_review')
                                            <x-icon name="o-clock" class="size-4" />
                                        @elseif(($testStatus['pretest'] ?? '') === 'available')
                                            <x-icon name="o-play" class="size-4" />
                                        @else
                                            <x-icon name="o-minus" class="size-4" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Pre-Test</p>
                                        @if (($testStatus['pretest'] ?? '') === 'completed')
                                            <p class="text-xs text-success">Score: {{ $testStatus['pretestScore'] ?? 0 }}%</p>
                                        @elseif(($testStatus['pretest'] ?? '') === 'under_review')
                                            <p class="text-xs text-warning">Under review</p>
                                        @elseif(($testStatus['pretest'] ?? '') === 'available')
                                            <p class="text-xs text-base-content/60">Ready to take</p>
                                        @else
                                            <p class="text-xs text-base-content/40">Not available</p>
                                        @endif
                                    </div>
                                </div>
                                @if (($testStatus['pretest'] ?? '') === 'available')
                                    <x-button wire:click="startPreTest" label="Start" class="btn-sm btn-primary" />
                                @elseif(($testStatus['pretest'] ?? '') === 'completed')
                                    <span class="badge badge-success badge-sm">Done</span>
                                @endif
                            </div>

                            {{-- Post-Test --}}
                            <div class="flex items-center justify-between p-3 rounded-lg bg-base-100 border border-base-200 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs
                                        @if (($testStatus['posttest'] ?? '') === 'completed') bg-success/20 text-success
                                        @elseif(($testStatus['posttest'] ?? '') === 'under_review') bg-warning/20 text-warning
                                        @elseif(($testStatus['posttest'] ?? '') === 'available') bg-primary/20 text-primary
                                        @elseif(($testStatus['posttest'] ?? '') === 'retake') bg-error/20 text-error
                                        @elseif(($testStatus['posttest'] ?? '') === 'failed') bg-error/20 text-error
                                        @elseif(($testStatus['posttest'] ?? '') === 'locked') bg-base-300 text-base-content/50
                                        @else bg-base-300 text-base-content/40 @endif">
                                        @if (($testStatus['posttest'] ?? '') === 'completed')
                                            <x-icon name="o-check" class="size-4" />
                                        @elseif(($testStatus['posttest'] ?? '') === 'under_review')
                                            <x-icon name="o-clock" class="size-4" />
                                        @elseif(($testStatus['posttest'] ?? '') === 'available')
                                            <x-icon name="o-play" class="size-4" />
                                        @elseif(($testStatus['posttest'] ?? '') === 'retake')
                                            <x-icon name="o-arrow-path" class="size-4" />
                                        @elseif(($testStatus['posttest'] ?? '') === 'failed')
                                            <x-icon name="o-x-mark" class="size-4" />
                                        @elseif(($testStatus['posttest'] ?? '') === 'locked')
                                            <x-icon name="o-lock-closed" class="size-4" />
                                        @else
                                            <x-icon name="o-minus" class="size-4" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Post-Test</p>
                                        @if (($testStatus['posttest'] ?? '') === 'completed')
                                            <p class="text-xs text-success">Passed: {{ $testStatus['posttestScore'] ?? 0 }}%</p>
                                        @elseif(($testStatus['posttest'] ?? '') === 'under_review')
                                            <p class="text-xs text-warning">Under review</p>
                                        @elseif(($testStatus['posttest'] ?? '') === 'available')
                                            <p class="text-xs text-base-content/60">Ready to take</p>
                                        @elseif(($testStatus['posttest'] ?? '') === 'retake')
                                            <p class="text-xs text-error">Failed: {{ $testStatus['posttestScore'] ?? 0 }}%</p>
                                        @elseif(($testStatus['posttest'] ?? '') === 'failed')
                                            <p class="text-xs text-error">Failed (Max attempts)</p>
                                        @elseif(($testStatus['posttest'] ?? '') === 'locked')
                                            <p class="text-xs text-base-content/50">Locked</p>
                                        @else
                                            <p class="text-xs text-base-content/40">Not available</p>
                                        @endif
                                    </div>
                                </div>
                                @if (($testStatus['posttest'] ?? '') === 'available')
                                    <x-button wire:click="startPostTest" label="Start" class="btn-sm btn-primary" />
                                @elseif(($testStatus['posttest'] ?? '') === 'retake')
                                    <x-button wire:click="startPostTest" label="Retake" class="btn-sm btn-error btn-outline" />
                                @elseif(($testStatus['posttest'] ?? '') === 'completed')
                                    <span class="badge badge-success badge-sm">Passed</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                <livewire:components.training-schedule.tabs.training-information-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                    :key="'info-' . $selectedEvent['id'] . '-' . $dayNumber" />
            @endif

            @anyrole('admin', 'instructor', 'certificator')
                {{-- Attendance Section --}}
                @if (!$isLms && $activeTab === 'attendance')
                    <livewire:components.training-schedule.tabs.training-attendance-tab :training-id="$selectedEvent['id']" :day-number="$dayNumber"
                        :key="'att-' . $selectedEvent['id'] . '-' . $dayNumber" lazy />
                @endif

                {{-- Close Training Section --}}
                @if ($activeTab === 'close-training')
                    <livewire:components.training-schedule.tabs.training-close-tab :training-id="$selectedEvent['id']" :key="'close-' . $selectedEvent['id']" lazy />
                @endif
            @endanyrole

            <div class="flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-5">
                <x-button wire:click="closeModal"
                    class="btn bg-white hover:bg-gray-100 hover:opacity-80 w-full sm:w-auto">Close</x-button>
                @anyrole('admin', 'instructor', 'certificator')
                    @php
                        $trainingStatus = strtolower($selectedEvent['status'] ?? '');
                        $canCloseTraining = !in_array($trainingStatus, ['done', 'approved', 'rejected']);
                    @endphp
                    @if ($activeTab === 'close-training' && $canCloseTraining)
                        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                            <x-button wire:click="triggerSaveDraft" spinner="triggerSaveDraft"
                                class="btn btn-outline btn-primary">
                                <x-icon name="o-document-text" />
                                <span>Save Draft</span>
                            </x-button>
                            <x-button wire:click="triggerCloseTraining" spinner="triggerCloseTraining"
                                class="btn btn-primary">
                                <x-icon name="o-check-circle" />
                                <span>Close Training</span>
                            </x-button>
                        </div>
                    @endif
                @endanyrole
            </div>
        </x-modal>
    @endif
</div>
