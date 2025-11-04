<div>
    <div class="w-full grid gap-10 lg:gap-5 mb-5 lg:mb-9 grid-cols-1 lg:grid-cols-2 items-center">
        <div class="flex items-center mb-2 gap-5">
            <a href="{{ route('survey-template.index') }}"
                class="flex items-center gap-1 text-primary hover:underline text-sm font-medium">
                <x-icon name="o-arrow-left" class="size-5" />
                Back
            </a>
            <h1 class="text-primary text-3xl font-bold text-center lg:text-start">
                Survey Template
            </h1>
        </div>
        <div class="col-span-full flex flex-col items-center lg:items-start mt-2">
            <div class="text-lg font-semibold text-base-content/90">
                {{ $template->title ?? '-' }}
            </div>
            <div class="text-sm text-base-content/60">
                Level: <span class="font-medium">{{ $template->level ?? '-' }}</span>
            </div>
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
                <livewire:components.survey-template.edit-template-questions :surveyLevel="$surveyLevel" :surveyId="$surveyId" lazy />
            </x-tab>

        </x-tabs>
    </div>



</div>
