<div>
    <div x-data="{ open: @entangle('show') }">
        <div x-cloak x-show="open" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4">
            <div @click.away="open=false; $wire.close()" class="bg-white rounded-lg shadow w-full max-w-md p-5 space-y-5">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Import Trainings</h2>
                    <button class="text-gray-400 hover:text-gray-600" @click="open=false; $wire.close()">✕</button>
                </div>
                <div class="text-xs text-gray-600 space-y-2">
                    <p>Upload an Excel file (.xlsx / .xls) following the template headers.</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>Type: IN / OUT / LMS</li>
                        <li>Participants separated by comma</li>
                        <li>LMS: fill Course Title (LMS), leave Training Name / Trainer / Times blank</li>
                        <li>IN/OUT: require Training Name, Trainer Name, Room, Start & End Time (HH:MM)</li>
                    </ul>
                </div>
                <div class="space-y-2">
                    <div class="text-xs font-semibold text-gray-700">Excel File</div>
                    <div class="flex items-stretch border border-gray-300 rounded overflow-hidden bg-white">
                        <div class="flex-1 flex items-center px-3 text-xs text-gray-600 truncate" x-data>
                            @if ($file)
                                <span class="truncate"
                                    title="{{ $file?->getClientOriginalName() }}">{{ $file?->getClientOriginalName() }}</span>
                            @else
                                <span class="text-gray-400">Select .xlsx / .xls file</span>
                            @endif
                        </div>
                        <div class="w-px bg-gray-200"></div>
                        <label
                            class="px-3 py-2 text-xs bg-gray-50 hover:bg-gray-100 cursor-pointer flex items-center gap-1">
                            <span>Browse</span>
                            <input type="file" wire:model="file" accept=".xlsx,.xls" class="hidden" />
                        </label>
                        @if ($file)
                            <button type="button" wire:click="$set('file', null)"
                                class="px-3 py-2 text-xs bg-red-50 hover:bg-red-100 text-red-600">Clear</button>
                        @endif
                    </div>
                    <div class="text-[11px] text-gray-500 flex items-center gap-2 min-h-[18px]">
                        <span wire:loading wire:target="file" class="animate-pulse">Checking file...</span>
                        @if ($fileReady)
                            <span wire:loading.remove wire:target="file" class="text-green-600">File valid. Click
                                Import.</span>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button @click="open=false; $wire.close()" type="button"
                        class="px-3 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-50">Cancel</button>
                    <button wire:click="import" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded bg-primary text-white hover:bg-primary/90 disabled:opacity-60">Import
                        <span wire:loading wire:target="import" class="ml-1 animate-pulse">...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
