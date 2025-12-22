<div>
    <div class="w-full flex justify-between mb-5 lg:mb-9 items-center">
        <div class="flex items-center gap-4">
            <x-button class="btn btn-ghost btn-sm" icon="o-arrow-left" wire:click="back">Back</x-button>
            <div class="flex flex-col">
                <h1 class="text-primary text-2xl sm:text-3xl font-bold leading-tight">
                    Survey Editor
                </h1>
                <div class="mt-1 flex items-center gap-2 text-sm text-base-content/70">
                    <span class="badge badge-primary badge-soft">Level {{ $surveyLevel }}</span>
                    <span class="badge badge-ghost" title="Survey ID: {{ $surveyId }}">Survey:
                        {{ $surveyName ?: '#' . $surveyId }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <x-tabs wire:model="activeTab" class="bg-base-100 p-3 pt-0 rounded-box shadow-sm mt-5">

            {{-- TAB: Participants Information --}}
            <x-tab name="participants-info" label="Participants Information" icon="m-user-group">
                <livewire:components.survey.survey-participants :surveyLevel="$surveyLevel" :surveyId="$surveyId" lazy />
            </x-tab>

            {{-- TAB: Edit Survey Questions --}}
            <x-tab name="survey-questions" label="Survey Questions" icon="m-clipboard-document-list">
                <livewire:components.survey.edit-survey-form :surveyLevel="$surveyLevel" :surveyId="$surveyId" lazy />
            </x-tab>

        </x-tabs>

        <!-- Back To Top Button -->
        <x-ui.button id="backToTopBtn" type="button" variant="primary"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2 !rounded-full shadow-lg hover:shadow-xl focus:outline-none focus:ring focus:ring-primary/40
        opacity-0 translate-y-2 pointer-events-none transition-all duration-300 tooltip tooltip-top"
            data-tip="Back to top" aria-hidden="true">
            <x-icon name="o-arrow-up" class="size-4" />
        </x-ui.button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('backToTopBtn');
            if (!btn) return;

            function showBtn() {
                btn.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
                btn.classList.add('opacity-100', 'translate-y-0');
                btn.setAttribute('aria-hidden', 'false');
            }

            function hideBtn() {
                btn.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
                btn.classList.remove('opacity-100', 'translate-y-0');
                btn.setAttribute('aria-hidden', 'true');
            }

            function toggleBtn() {
                if (window.scrollY > 160) {
                    showBtn();
                } else {
                    hideBtn();
                }
            }

            window.addEventListener('scroll', toggleBtn, {
                passive: true
            });
            toggleBtn();

            btn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
