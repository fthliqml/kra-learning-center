@php($sectionErr = in_array('t' . $ti . '-s' . $si, $errorSectionKeys ?? []))
@php($quizEnabled = data_get($topics ?? [], $ti . '.sections.' . $si . '.quiz.enabled', false))
<div class="relative group px-4 py-4 space-y-4 section-card rounded-md {{ $sectionErr ? 'ring-1 ring-error/60 border border-error/60 bg-error/5' : '' }}"
    data-id="{{ $section['id'] }}" wire:key="topic-{{ $topic['id'] }}-section-{{ $section['id'] }}"
    x-init="(() => {
        const parent = $el.closest('.topic-card');
        if (!parent) return;
        let container = parent.querySelector('.sections-container');
        if (container && !container._sortableInit && typeof Sortable !== 'undefined') {
            container._sortableInit = true;
            new Sortable(container, {
                handle: '.section-drag-handle',
                animation: 150,
                draggable: '.section-card',
                onEnd: () => {
                    const ids = [...container.querySelectorAll('.section-card')].map(el => el.dataset.id);
                    const topicIndex = [...document.querySelectorAll('#topics-list .topic-card')].indexOf(parent);
                    if (topicIndex >= 0) {
                        $wire.reorderSections(topicIndex, ids);
                    }
                }
            });
        }
    })()">
    <span class="absolute left-0 top-0 h-full w-1 bg-primary opacity-70 group-hover:opacity-90 transition"
        aria-hidden="true"></span>
    <div class="flex items-start gap-3">
        <span
            class="inline-flex items-center justify-center w-12 h-6 shrink-0 rounded bg-primary/15 text-primary text-[10px] font-semibold tracking-wide">{{ $ti + 1 . '.' . ($si + 1) }}</span>
        <div class="flex-1 space-y-2">
            <x-input placeholder="Sub Topic Title"
                wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.title" />
            <div class="flex gap-2 flex-wrap">
                <x-button type="button" size="xs" icon="o-plus"
                    wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'pdf')" outline
                    class="border-gray-400">PDF</x-button>
                <x-button type="button" size="xs" icon="o-plus"
                    wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'youtube')" outline
                    class="border-gray-400">YouTube</x-button>
                <x-button type="button" size="xs" :variant="$quizEnabled ? 'primary' : 'ghost'"
                    wire:click="toggleSectionQuiz({{ $ti }}, {{ $si }})" icon="o-academic-cap"
                    class="border-gray-400">{{ $quizEnabled ? 'Quiz ON' : 'Quiz OFF' }}</x-button>
            </div>
        </div>
        <div class="flex items-center gap-1 self-start ml-2">
            <button type="button" title="Drag to reorder"
                class="section-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-primary btn btn-ghost btn-xs">
                <x-icon name="o-bars-3" class="size-5" />
            </button>
            <x-button type="button" icon="o-trash" class="btn-ghost text-error" title="Delete Sub Topic"
                wire:click="removeSection({{ $ti }}, {{ $si }})" />
        </div>
    </div>

    @if (!empty($section['resources']))
        <div class="flex flex-col gap-4">
            @include(
                'components.edit-course.partials._resources-youtube',
                compact('ti', 'si', 'section', 'errorResourceKeys'))
            @include(
                'components.edit-course.partials._resources-pdf',
                compact('ti', 'si', 'section', 'errorResourceKeys'))
        </div>
    @else
        <p class="text-[11px] italic text-base-content/50">No resources added yet.</p>
    @endif

    @if ($quizEnabled)
        <div class="mt-1 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold tracking-wide uppercase text-base-content/70">Quiz
                    Questions</span>
                <x-button type="button" size="xs" icon="o-plus"
                    wire:click="addSectionQuizQuestion({{ $ti }}, {{ $si }})" outline
                    class="border-gray-400">Add Question</x-button>
            </div>
            <div class="flex flex-col gap-2">
                @forelse ($section['quiz']['questions'] ?? [] as $qi => $qq)
                    @include(
                        'components.edit-course.partials._quiz-question',
                        compact('ti', 'si', 'qi', 'qq', 'section', 'errorQuestionKeys'))
                @empty
                    <p class="text-[11px] italic text-base-content/50">No questions yet.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
