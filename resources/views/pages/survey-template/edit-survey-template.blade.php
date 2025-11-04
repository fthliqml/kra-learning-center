<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <div class="flex items-center gap-5">
            <a href="{{ route('survey-template.index') }}"
                class="flex items-center gap-1 text-primary hover:underline text-sm font-medium">
                <x-icon name="o-arrow-left" class="size-5" />
                Back
            </a>
            <h1 class="text-primary text-3xl font-bold text-center lg:text-start">
                Survey Template
            </h1>
        </div>
        <div class="col-span-full flex flex-col items-center lg:items-start">
            <div class="text-lg font-semibold text-base-content/90">
                {{ $template?->title ?: 'New Survey Template' }}
            </div>
            <span class="badge badge-primary badge-soft">Level {{ $template?->level ?? 1 }}</span>
        </div>
    </div>

    <div class="space-y-6">
        <x-tabs wire:model="activeTab" class="bg-base-100 p-3 pt-0 !mt-[-3px] rounded-box shadow-sm">

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



</div>
