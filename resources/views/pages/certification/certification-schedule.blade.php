<div class="bg-white relative">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">Certification Schedule</h1>
        @role('admin')
            <div class="flex flex-wrap gap-2 items-center justify-center lg:justify-end">
                <livewire:components.certification.certification-form-modal />
            </div>
        @endrole
    </div>

    <livewire:components.certification.schedule-view />
    <livewire:components.shared.action-choice-modal />
    <livewire:components.certification.detail-certification-modal />
    <livewire:components.confirm-dialog />

</div>
