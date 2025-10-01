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
            <div class="rounded-xl p-5 bg-base-100 shadow ring-1 ring-base-300/50 border border-gray-400 space-y-5 topic-card relative"
                data-id="{{ $topic['id'] }}" wire:key="topic-{{ $topic['id'] }}">
                <div class="flex items-start gap-3">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-md bg-gradient-to-br from-secondary/20 to-secondary/10 text-secondary text-xs font-semibold shadow-inner">{{ $loop->iteration }}</span>
                    <div class="flex-1 space-y-2">
                        <x-input placeholder="Topic Title" wire:model.defer="topics.{{ $ti }}.title" />
                        <div class="flex gap-2 flex-wrap">
                            <x-button type="button" size="xs" icon="o-plus"
                                wire:click="addSection({{ $ti }})" outline class="border-gray-400">Add Sub
                                Topic</x-button>
                            <x-button type="button" size="xs" :icon="$collapsed ? 'o-chevron-down' : 'o-chevron-up'"
                                wire:click="setCollapsedTopic('{{ $topic['id'] }}', {{ $collapsed ? 'false' : 'true' }})"
                                outline class="border-gray-400">
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
                            <div class="relative group px-4 py-4 space-y-4 section-card" data-id="{{ $section['id'] }}"
                                wire:key="topic-{{ $topic['id'] }}-section-{{ $section['id'] }}"
                                x-init="(() => {
                                    // Initialize Sortable for sections container once (on parent) not each item
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
                                <span
                                    class="absolute left-0 top-0 h-full w-1 bg-primary opacity-70 group-hover:opacity-90 transition"
                                    aria-hidden="true"></span>
                                <div class="flex items-start gap-3">
                                    <span
                                        class="inline-flex items-center justify-center w-12 h-6 shrink-0 rounded bg-primary/15 text-primary text-[10px] font-semibold tracking-wide">{{ $ti + 1 . '.' . ($si + 1) }}</span>
                                    <div class="flex-1 space-y-2">
                                        <x-input placeholder="Sub Topic Title"
                                            wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.title" />
                                        <div class="flex gap-2 flex-wrap">
                                            <x-button type="button" size="xs" icon="o-plus"
                                                wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'pdf')"
                                                outline class="border-gray-400">PDF</x-button>
                                            <x-button type="button" size="xs" icon="o-plus"
                                                wire:click="addSectionResource({{ $ti }}, {{ $si }}, 'youtube')"
                                                outline class="border-gray-400">YouTube</x-button>
                                            <x-button type="button" size="xs" :variant="$topics[$ti]['sections'][$si]['quiz']['enabled'] ?? false
                                                ? 'primary'
                                                : 'ghost'"
                                                wire:click="toggleSectionQuiz({{ $ti }}, {{ $si }})"
                                                icon="o-academic-cap"
                                                class="border-gray-400">{{ $topics[$ti]['sections'][$si]['quiz']['enabled'] ?? false ? 'Quiz ON' : 'Quiz OFF' }}</x-button>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 self-start ml-2">
                                        <button type="button" title="Drag to reorder"
                                            class="section-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-primary btn btn-ghost btn-xs">
                                            <x-icon name="o-bars-3" class="size-5" />
                                        </button>
                                        <x-button type="button" icon="o-trash" class="btn-ghost text-error"
                                            title="Delete Sub Topic"
                                            wire:click="removeSection({{ $ti }}, {{ $si }})" />
                                    </div>
                                </div>

                                @if (!empty($section['resources']))
                                    @php($ytExists = collect($section['resources'])->contains(fn($r) => ($r['type'] ?? '') === 'youtube'))
                                    @php($pdfExists = collect($section['resources'])->contains(fn($r) => ($r['type'] ?? '') === 'pdf'))
                                    <div class="flex flex-col gap-4">
                                        @if ($ytExists)
                                            <div class="space-y-2">
                                                <p
                                                    class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">
                                                    YouTube Videos</p>
                                                @foreach ($section['resources'] as $ri => $res)
                                                    @if (($res['type'] ?? '') === 'youtube')
                                                        <div
                                                            class="flex items-start gap-3 rounded-md bg-base-100/70 px-3 py-2 shadow-sm ring-1 ring-base-300/40 border border-base-300/40 hover:ring-base-300 transition">
                                                            <div class="flex-1 space-y-1">
                                                                <x-input
                                                                    placeholder="https://www.youtube.com/watch?v=..."
                                                                    wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.url"
                                                                    wire:change="$refresh" />
                                                                @php($yt = $res['url'] ?? '')
                                                                @if ($yt && preg_match('/v=([\w-]+)/', $yt, $m))
                                                                    <iframe
                                                                        class="w-full max-w-md aspect-video rounded-md ring-1 ring-base-300/50"
                                                                        src="https://www.youtube.com/embed/{{ $m[1] }}"
                                                                        allowfullscreen></iframe>
                                                                @endif
                                                            </div>
                                                            <x-button type="button" icon="o-x-mark"
                                                                class="btn-ghost text-error" title="Delete"
                                                                wire:click="removeSectionResource({{ $ti }}, {{ $si }}, {{ $ri }})" />
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($pdfExists)
                                            <div class="space-y-2">
                                                <p
                                                    class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">
                                                    PDF Modules</p>
                                                @foreach ($section['resources'] as $ri => $res)
                                                    @if (($res['type'] ?? '') === 'pdf')
                                                        <div
                                                            class="flex items-start gap-3 rounded-md bg-base-100/70 px-3 py-2 shadow-sm ring-1 ring-base-300/40 border border-base-300/40 hover:ring-base-300 transition">
                                                            <div class="flex-1 space-y-1">
                                                                <input type="file" accept="application/pdf"
                                                                    wire:model="topics.{{ $ti }}.sections.{{ $si }}.resources.{{ $ri }}.file"
                                                                    class="file-input file-input-bordered file-input-sm w-full max-w-md" />
                                                                @php($pdfUrl = $res['url'] ?? '')
                                                                @if ($pdfUrl)
                                                                    <a href="{{ $pdfUrl }}" target="_blank"
                                                                        class="mt-1 inline-flex items-center gap-1 text-[11px] text-primary hover:underline">
                                                                        <x-icon name="o-arrow-top-right-on-square"
                                                                            class="size-3" />
                                                                        <span>Open PDF</span>
                                                                    </a>
                                                                @else
                                                                    <p class="text-[11px] text-gray-400">No file
                                                                        uploaded yet.</p>
                                                                @endif
                                                            </div>
                                                            <x-button type="button" icon="o-x-mark"
                                                                class="btn-ghost text-error" title="Delete"
                                                                wire:click="removeSectionResource({{ $ti }}, {{ $si }}, {{ $ri }})" />
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-[11px] italic text-base-content/50">No resources added yet.</p>
                                @endif

                                @if ($section['quiz']['enabled'] ?? false)
                                    <div class="mt-1 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span
                                                class="text-[11px] font-semibold tracking-wide uppercase text-base-content/70">Quiz
                                                Questions</span>
                                            <x-button type="button" size="xs" icon="o-plus"
                                                wire:click="addSectionQuizQuestion({{ $ti }}, {{ $si }})"
                                                outline class="border-gray-400">Add Question</x-button>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            @forelse ($section['quiz']['questions'] ?? [] as $qi => $qq)
                                                <div class="relative rounded-md p-3 pr-10 bg-base-100/70 ring-1 ring-base-300/40 question-card"
                                                    wire:key="sec-q-{{ $section['id'] }}-{{ $qq['id'] }}">
                                                    <span
                                                        class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-info/60 to-info/10"></span>
                                                    <div class="flex items-center gap-3 mb-3">
                                                        <span
                                                            class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-info/10 text-info text-xs font-semibold">{{ $loop->iteration }}</span>
                                                        <x-select :options="[
                                                            ['value' => 'multiple', 'label' => 'Multiple'],
                                                            ['value' => 'essay', 'label' => 'Essay'],
                                                        ]" option-value="value"
                                                            option-label="label" class="w-40"
                                                            wire:model="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.type"
                                                            wire:change="$refresh" />
                                                        <div class="flex-1 relative">
                                                            <x-input class="w-full pr-10 focus-within:border-0"
                                                                placeholder="Write the question"
                                                                wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.question" />
                                                            <button type="button" title="Delete question"
                                                                class="absolute inset-y-0 right-0 my-[3px] mr-1 flex items-center justify-center h-8 w-8 rounded-md text-red-500 border border-transparent hover:bg-red-50"
                                                                wire:click="removeSectionQuizQuestion({{ $ti }}, {{ $si }}, {{ $qi }})">
                                                                <x-icon name="o-trash" class="size-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @if (($qq['type'] ?? '') === 'multiple')
                                                        <div class="space-y-2">
                                                            @foreach ($qq['options'] ?? [] as $oi => $opt)
                                                                <div class="flex items-center gap-2"
                                                                    wire:key="sec-q-{{ $qq['id'] }}-opt-{{ $oi }}">
                                                                    <x-input class="flex-1 pr-10 focus-within:border-0"
                                                                        placeholder="Option"
                                                                        wire:model.defer="topics.{{ $ti }}.sections.{{ $si }}.quiz.questions.{{ $qi }}.options.{{ $oi }}" />
                                                                    <x-button icon="o-x-mark"
                                                                        class="btn-ghost text-error"
                                                                        title="Remove option"
                                                                        wire:click="removeSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }}, {{ $oi }})" />
                                                                </div>
                                                            @endforeach
                                                            <x-button type="button" size="xs"
                                                                class="border-gray-400" outline icon="o-plus"
                                                                wire:click="addSectionQuizOption({{ $ti }}, {{ $si }}, {{ $qi }})">Add
                                                                Option</x-button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                <p class="text-[11px] italic text-base-content/50">No questions yet.
                                                </p>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif(!$collapsed)
                    <p class="text-xs italic text-base-content/50">No sub topics yet.</p>
                @endif
            </div>
        @empty
            <div class="text-xs text-gray-400 italic">No topics have been added yet.</div>
        @endforelse
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-base-300/50 mt-4">
        <div class="flex items-center gap-4">
            <x-ui.save-draft-status action="saveDraft" :dirty="$isDirty" :ever="$hasEverSaved" :persisted="$persisted" />
        </div>
        <div class="flex gap-2 ml-auto">
            <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack"
                title="Go to previous step">
                <x-icon name="o-arrow-left" class="size-4" />
                <span>Back</span>
            </x-ui.button>
            <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goNext"
                title="Go to next step">
                <span>Next</span>
                <x-icon name="o-arrow-right" class="size-4" />
            </x-ui.button>
        </div>
    </div>
</div>
