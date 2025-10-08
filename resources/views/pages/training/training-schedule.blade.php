<div class="bg-white relative">
    <div class="flex justify-between items-center">
        <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">
            Training Schedule
        </h1>

        @role('admin')
            <livewire:components.training.training-form-modal />
        @endrole
    </div>

    <livewire:components.training.schedule-view />
    <livewire:components.training.detail-training-modal />
    <livewire:components.confirm-dialog />

</div>
