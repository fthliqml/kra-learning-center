<div class="space-y-4" x-data>
    <div id="pretest-list" class="space-y-4" x-init="(() => {
        const list = $el;
        new Sortable(list, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: () => {
                const ids = [...list.querySelectorAll('.question-card')].map(el => el.dataset.id);
                $wire.reorderByIds(ids);
            }
        });
    })()">
        @forelse ($questions as $i => $q)
            <div class="border rounded-xl p-3 pr-10 bg-base-100 question-card relative" data-id="{{ $q['id'] }}"
                wire:key="pre-q-{{ $q['id'] }}">
                <button type="button" data-tip="drag"
                    class="drag-handle tooltip absolute top-1/2 -translate-y-1/2 right-2 cursor-grab active:cursor-grabbing text-gray-400 hover:text-primary z-10"
                    title="Drag to reorder">
                    <x-icon name="o-bars-3" class="size-5" />
                </button>
                <div class="flex items-center gap-3 mb-3">
                    <span
                        class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ $loop->iteration }}</span>
                    <x-select :options="[
                        ['value' => 'multiple', 'label' => 'Multiple Choice'],
                        ['value' => 'essay', 'label' => 'Essay'],
                    ]" option-value="value" option-label="label" class="w-52"
                        wire:model="questions.{{ $i }}.type" wire:change="$refresh" />
                    <div class="flex-1 relative">
                        <x-input class="w-full pr-10 focus-within:border-0" placeholder="Write the question"
                            wire:model.defer="questions.{{ $i }}.question" />
                        <button type="button" title="Remove question"
                            class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                            wire:click="removeQuestion({{ $i }})">
                            <x-icon name="o-trash" class="size-4" />
                        </button>
                    </div>
                </div>
                @if (($q['type'] ?? '') === 'multiple')
                    <div class="space-y-2">
                        @foreach ($q['options'] ?? [] as $oi => $opt)
                            <div class="flex items-center gap-2"
                                wire:key="pre-q-{{ $q['id'] }}-opt-{{ $oi }}">
                                <x-input class="flex-1 pr-10 focus-within:border-0" placeholder="Option text"
                                    wire:model.defer="questions.{{ $i }}.options.{{ $oi }}" />
                                <x-button icon="o-trash" class="btn-ghost text-red-500" title="Remove option"
                                    wire:click="removeOption({{ $i }}, {{ $oi }})" />
                            </div>
                        @endforeach
                        <x-button type="button" size="sm" class="border-gray-400" outline icon="o-plus"
                            wire:click="addOption({{ $i }})">Add Option</x-button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed p-8 text-center text-sm text-gray-500 bg-white/50">
                No questions yet. Click <span class="font-medium">Add Question</span> to create the first one.
            </div>
        @endforelse
    </div>
    <x-button type="button" variant="primary" outline icon="o-plus" wire:click="addQuestion"
        class="border-gray-400">Add Question</x-button>
    <div class="flex items-center justify-between pt-2">
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack">
            <x-icon name="o-arrow-left" class="size-4" />
            <span>Back</span>
        </x-ui.button>
        <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNext">
            <span>Next</span>
            <x-icon name="o-arrow-right" class="size-4" />
        </x-ui.button>
    </div>
</div>
