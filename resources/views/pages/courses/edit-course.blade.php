<div class="space-y-6">
    <x-tabs wire:model="activeTab" class="bg-base-100 p-3 rounded-box shadow-sm">
        {{-- TAB: Course Info --}}
        <x-tab name="course-info" label="Course Info" icon="m-document-text">
            <livewire:components.courses.course-info />
        </x-tab>

        {{-- TAB: Pretest --}}
        <x-tab name="pretest" label="Pretest" icon="m-clipboard-document-list">
            <livewire:components.courses.pretest-questions />
        </x-tab>

        {{-- TAB: Learning Module --}}
        <x-tab name="learning-module" label="Learning Module" icon="m-academic-cap">
            <livewire:components.courses.learning-modules />
        </x-tab>

        {{-- TAB: Post Test --}}
        <x-tab name="post-test" label="Post Test" icon="m-check-badge">
            <livewire:components.courses.post-test-questions />
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
