<div class="space-y-6" x-data>
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold">Topics</h3>
        <x-button type="button" size="sm" icon="o-plus" wire:click="addTopic" class="border-gray-400" outline>
            Add Topic
        </x-button>
    </div>

    <div id="topics-list" class="space-y-6" x-init="(() => {
        const list = $el;
        if (typeof Sortable !== 'undefined') {
            new Sortable(list, {
                handle: '.topic-drag-handle',
                animation: 150,
                draggable: '.topic-card',
                onEnd: () => {
                    const ids = [...list.querySelectorAll('.topic-card')].map(el => el.dataset.id);
                    $wire.reorderTopics(ids);
                }
            });
        }
    })()">
        @forelse($topics as $ti => $topic)
            @php($collapsed = in_array($topic['id'], $collapsedTopicIds ?? []))
            @include(
                'components.edit-course.partials._topic-card',
                compact(
                    'topic',
                    'ti',
                    'collapsed',
                    'errorTopicKeys',
                    'errorSectionKeys',
                    'errorResourceKeys',
                    'errorQuestionKeys',
                    'topics',
                    'collapsedTopicIds'))
        @empty
            <div class="text-xs text-gray-400 italic">No topics have been added yet.</div>
        @endforelse
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-base-300/50 mt-4">
        <div class="flex items-center gap-3 mt-5">
            <x-ui.button type="button" variant="secondary" class="border-gray-300" wire:click="saveDraft"
                wire:loading.attr="disabled" wire:target="saveDraft" spinner="saveDraft">
                <x-icon name="o-bookmark" class="size-4" />
                <span>Save Draft</span>
            </x-ui.button>
            <x-ui.save-draft-status :dirty="$isDirty ?? false" :ever="$hasEverSaved ?? false" :persisted="$persisted ?? false" />
        </div>
        <div class="flex gap-2 ml-auto mt-5">
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
</div>
