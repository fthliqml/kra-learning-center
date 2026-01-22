<div class="space-y-6">
    {{-- Header with Back Button and Course Title --}}
    <div class="flex items-center gap-4 bg-base-100 p-4 rounded-box shadow-sm">
        <a href="{{ route('courses-management.index') }}" 
           class="btn btn-ghost btn-sm btn-circle hover:bg-base-200" 
           title="Back to Course Management">
            <x-icon name="o-arrow-left" class="w-5 h-5" />
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-semibold text-gray-800">
                {{ $course->title ?? 'New Course' }}
            </h1>
            <p class="text-sm text-gray-500">
                @if($isCreating)
                    Creating new course
                @else
                    Editing course details
                @endif
            </p>
        </div>
    </div>

    <x-tabs wire:model="activeTab" class="bg-base-100 p-3 rounded-box shadow-sm pt-0">
        {{-- TAB: Course Info --}}
        <x-tab name="course-info" label="Course Info" icon="m-document-text">
            <livewire:components.edit-course.course-info :courseId="$course->id" lazy />
        </x-tab>

        {{-- TAB: Pre-Test --}}
        <x-tab name="pretest" label="Pre-Test" icon="m-clipboard-document-list" :disabled="!$courseId">
            <livewire:components.edit-course.pretest-questions :courseId="$course->id" lazy />
        </x-tab>

        {{-- TAB: Learning Module --}}
        <x-tab name="learning-module" label="Learning Module" icon="m-academic-cap" :disabled="!$courseId">
            <livewire:components.edit-course.learning-modules :courseId="$course->id" lazy />
        </x-tab>

        {{-- TAB: Post-Test --}}
        <x-tab name="post-test" label="Post-Test" icon="m-check-badge" :disabled="!$courseId">
            <livewire:components.edit-course.post-test-questions :courseId="$course->id" lazy />
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

    // Listen for course-finished event and redirect after delay
    // This allows all save-all-drafts listeners to complete before navigating away
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('course-finished', (event) => {
            // Delay redirect to allow Livewire to process all pending save operations
            setTimeout(() => {
                window.location.href = event.url;
            }, 600);
        });
    });
</script>
