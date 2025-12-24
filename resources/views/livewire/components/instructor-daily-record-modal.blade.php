<div>
    <x-modal wire:model="showModal" box-class="max-w-3xl" persistent>
        {{-- Header with close button --}}
        <div class="flex items-center justify-between pb-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">
                {{ $isEdit ? 'Edit Instructor Daily Record' : 'Add Instructor Daily Record' }}
            </h3>
            <button type="button" wire:click="closeModal"
                class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                <x-icon name="o-x-mark" class="w-5 h-5" />
            </button>
        </div>

        {{-- Form Content --}}
        <x-form wire:submit="save" no-separator>
            {{-- Section 1: Date, NRP, Utilization Info --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                {{-- Date --}}
                <div>
                    <x-datepicker wire:model="date" label="Date" icon="o-calendar" class="focus-within:border-0"
                        :config="[
                            'altInput' => true,
                            'altFormat' => 'd-m-Y',
                            'dateFormat' => 'Y-m-d',
                        ]" />
                </div>

                {{-- Instructor (NRP) - Auto from Auth --}}
                <div>
                    <x-input label="NRP / Instructor" value="{{ $instructorDisplay }}" readonly icon="o-user"
                        class="focus-within:border-0 bg-gray-50" />
                </div>

                {{-- Record Utilization (readonly) --}}
                <div>
                    <x-input label="Record Utilization" value="{{ $recordUtilization }} Minute" readonly icon="o-clock"
                        class="focus-within:border-0 bg-gray-50" hint="Auto-calculated from job items" />
                </div>

                {{-- Attendance + SPL (hardcoded) --}}
                <div>
                    <x-input label="Attendance + SPL" value="{{ $attendanceSpl }} Minute / 7.5 Hour" readonly
                        icon="o-calendar-days" class="focus-within:border-0 bg-gray-50" hint="Fixed value" />
                </div>

                {{-- Available Minutes --}}
                <div class="sm:col-span-2">
                    <x-input label="Available" value="{{ $available }} Minute" readonly icon="o-chart-bar"
                        class="focus-within:border-0 {{ $available < 0 ? 'bg-red-50 text-red-600' : 'bg-green-50' }}"
                        hint="Remaining available time" />
                </div>
            </div>

            {{-- Separator --}}
            <hr class="my-6 border-gray-200" />

            {{-- Section 2: Job Items --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700">Job Description Items</h4>
                    <span class="text-xs text-gray-500">Add activity details below</span>
                </div>

                {{-- Job Items Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[40%]">Job
                                    Description</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[30%]">Remark</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[20%]">Manhour
                                    (minute)</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-600 w-[10%]"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($jobItems as $index => $item)
                                <tr wire:key="job-item-{{ $index }}">
                                    <td class="px-2 py-2">
                                        <x-select wire:model.live="jobItems.{{ $index }}.job_desc"
                                            :options="$jobDescOptions" option-value="value" option-label="label"
                                            placeholder="Select job desc..."
                                            class="!min-h-0 !h-9 text-xs focus-within:border-0" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <x-input wire:model="jobItems.{{ $index }}.remark"
                                            placeholder="Optional remark..."
                                            class="!min-h-0 !h-9 text-xs focus-within:border-0" />
                                    </td>
                                    <td class="px-2 py-2">
                                        <x-input type="number"
                                            wire:model.live.debounce.300ms="jobItems.{{ $index }}.manhour"
                                            placeholder="Minutes" min="1" max="450"
                                            class="!min-h-0 !h-9 text-xs focus-within:border-0" />
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        @if (count($jobItems) > 1)
                                            <button type="button" wire:click="removeRow({{ $index }})"
                                                class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors">
                                                <x-icon name="o-trash" class="w-4 h-4" />
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Add Row Button --}}
                <div class="flex justify-start">
                    <x-button type="button" wire:click="addRow" class="btn-ghost btn-sm text-primary">
                        <x-icon name="o-plus" class="w-4 h-4" />
                        Add Row
                    </x-button>
                </div>

                {{-- Validation Errors --}}
                @error('jobItems')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
                @error('jobItems.*')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <x-slot:actions>
                <div class="flex justify-between items-center w-full pt-4 border-t border-gray-200">
                    <x-button type="button" wire:click="closeModal" class="btn-ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" class="btn-primary" spinner="save">
                        <x-icon name="o-bookmark" class="w-4 h-4" />
                        {{ $isEdit ? 'Update' : 'Save' }}
                    </x-button>
                </div>
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
