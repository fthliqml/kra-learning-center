<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('survey-template.index') }}" wire:navigate class="btn btn-ghost btn-sm btn-circle">
                <x-icon name="o-arrow-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold text-primary">
                    Survey Template
                </h1>
                <p class="text-sm text-base-content/60 flex flex-wrap items-center gap-2">
                    <span>{{ $template?->title ?: 'New Survey Template' }}</span>
                </p>
            </div>
        </div>
    </div>

    <x-tabs wire:model="activeTab" class="bg-base-100 p-3 pt-0 mt-5 rounded-box shadow-sm">
        {{-- TAB: Template Information --}}
        <x-tab name="template-info" label="Template Information" icon="m-document-text">
            <livewire:components.survey-template.template-information :surveyLevel="$surveyLevel" :surveyId="$surveyId" lazy />
        </x-tab>

        {{-- TAB: Questions --}}
        <x-tab name="questions" label="Questions" icon="m-clipboard-document-list">
            @if (!empty($template?->id))
                <livewire:components.survey-template.edit-template-questions :surveyLevel="$surveyLevel" :surveyId="$surveyId"
                    lazy />
            @else
                <div class="p-6 border border-dashed rounded-lg bg-base-100 text-sm text-base-content/70">
                    <div class="flex items-start gap-3">
                        <x-icon name="o-information-circle" class="size-5 text-primary mt-0.5" />
                        <div>
                            <div class="font-semibold text-base-content/90">Complete Template Information first
                            </div>
                            <p class="mt-1">Please save the template title, description, and level before adding
                                questions.</p>
                            <div class="mt-3">
                                <x-button class="btn btn-primary btn-sm" icon="o-pencil"
                                    wire:click="$set('activeTab','template-info')">Go to Template
                                    Information</x-button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </x-tab>
    </x-tabs>
</div>
