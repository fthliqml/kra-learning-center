@php($topicErr = in_array('t' . $ti, $errorTopicKeys ?? []))
<div class="rounded-xl p-5 bg-base-100 shadow ring-1 space-y-5 topic-card relative {{ $topicErr ? 'ring-error/70 border border-error/70 bg-error/5' : 'ring-base-300/50 border border-gray-400' }}"
    data-id="{{ $topic['id'] }}" wire:key="topic-{{ $topic['id'] }}">
    <div class="flex items-start gap-3">
        <span
            class="inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-md bg-gradient-to-br from-secondary/20 to-secondary/10 text-secondary text-xs font-semibold shadow-inner">{{ $loop->iteration }}</span>
        <div class="flex-1 space-y-2">
            <x-input placeholder="Topic Title" wire:model.defer="topics.{{ $ti }}.title" />
            <div class="flex gap-2 flex-wrap">
                <x-button type="button" size="xs" icon="o-plus" wire:click="addSection({{ $ti }})"
                    outline class="border-gray-400">Add Sub Topic</x-button>
                <x-button type="button" size="xs" :icon="$collapsed ? 'o-chevron-down' : 'o-chevron-up'"
                    wire:click="setCollapsedTopic('{{ $topic['id'] }}', {{ $collapsed ? 'false' : 'true' }})" outline
                    class="border-gray-400">
                    {{ $collapsed ? 'Expand' : 'Collapse' }}
                </x-button>
            </div>
        </div>
        <div class="flex items-center gap-1 self-start ml-2">
            <button type="button" title="Drag to reorder"
                class="topic-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-primary btn btn-ghost btn-xs">
                <x-icon name="o-bars-3" class="size-5" />
            </button>
            <x-button type="button" icon="o-trash" class="btn-ghost text-red-500" title="Delete Topic"
                wire:click="removeTopic({{ $ti }})" />
        </div>
    </div>

    @if (!$collapsed && !empty($topic['sections']))
        <div
            class="divide-y divide-base-300/60 border border-base-300/40 rounded-md bg-base-200/30 overflow-hidden sections-container">
            @foreach ($topic['sections'] as $si => $section)
                @include(
                    'components.edit-course.partials._section-card',
                    compact(
                        'topic',
                        'section',
                        'ti',
                        'si',
                        'errorSectionKeys',
                        'errorResourceKeys',
                        'errorQuestionKeys',
                        'topics'))
            @endforeach
        </div>
    @elseif(!$collapsed)
        <p class="text-xs italic text-base-content/50">No sub topics yet.</p>
    @endif
</div>
