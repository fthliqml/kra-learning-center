<div class="bg-white relative">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">Training Schedule</h1>
        @role('admin')
            <div class="flex flex-wrap gap-2 items-center justify-center lg:justify-end">
                <x-button icon="o-funnel" label="Filter" class="btn-ghost border border-primary"
                    wire:click="$dispatch('open-schedule-filter')" />
                <livewire:components.training.schedule-excel-actions />
                <livewire:components.training.training-form-modal />
            </div>
        @endrole
    </div>

    <livewire:components.training.schedule-view />
    @role('admin')
        <livewire:components.training.schedule-filter-modal />
        <livewire:components.training.training-import-modal />
    @endrole
    <livewire:components.training.detail-training-modal />
    <livewire:components.confirm-dialog />

</div>
