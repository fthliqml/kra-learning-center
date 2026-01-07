<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('training-module.index') }}" wire:navigate class="btn btn-ghost btn-sm btn-circle">
                <x-icon name="o-arrow-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold text-primary">
                    {{ $isCreating ? 'Create Training Module' : 'Edit Training Module' }}
                </h1>
                <p class="text-sm text-base-content/60">
                    {{ $isCreating ? 'Setup new training module with pretest and post-test' : $module->title ?? 'Untitled Module' }}
                </p>
            </div>
        </div>
    </div>

    <x-tabs wire:model="activeTab" class="bg-base-100 p-3 rounded-box shadow-sm pt-0">
        {{-- TAB: Module Info --}}
        <x-tab name="module-info" label="Module Info" icon="m-document-text">
            <livewire:components.training-module.module-information :moduleId="$module->id ?? null" lazy />
        </x-tab>

        {{-- TAB: Pre-Test --}}
        <x-tab name="pretest" label="Pre-Test" icon="m-clipboard-document-list" :disabled="!$moduleId">
            <livewire:components.training-module.module-pretest :moduleId="$module->id ?? null" lazy />
        </x-tab>

        {{-- TAB: Post-Test --}}
        <x-tab name="posttest" label="Post-Test" icon="m-check-badge" :disabled="!$moduleId">
            <livewire:components.training-module.module-posttest :moduleId="$module->id ?? null" lazy />
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
