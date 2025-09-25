<div class="bg-white relative" x-data="{
    loadingDetail: false,
    overlayMessage: 'Loading...',
    timer: null,
    start(msg = 'Loading...') {
        this.overlayMessage = msg || 'Loading...';
        if (this.loadingDetail) return;
        this.loadingDetail = true;
        if (this.timer) clearTimeout(this.timer);
        this.timer = setTimeout(() => { this.loadingDetail = false; }, 12000);
    },
    stop() {
        this.loadingDetail = false;
        if (this.timer) clearTimeout(this.timer);
        this.timer = null;
    }
}"
    x-on:detail-loading-start.window="start('Opening training detail...')" x-on:training-detail-ready.window="stop()"
    x-on:global-overlay-start.window="start($event.detail?.message || 'Processing...')"
    x-on:global-overlay-stop.window="stop()">
    <div class="flex justify-between items-center">
        <h1 class="text-primary text-2xl sm:text-4xl font-bold text-center lg:text-start">
            Training Schedule
        </h1>

        <livewire:components.training.add-training-modal />
    </div>

    <livewire:components.training.schedule-view />
    <livewire:components.training.detail-training-modal />
    <livewire:components.confirm-dialog />

    <!-- Global overlay for training detail loading -->
    <div x-show="loadingDetail" x-cloak
        class="fixed inset-0 z-40 flex flex-col items-center justify-center bg-white/75 backdrop-blur-sm">
        <div class="flex items-center gap-3 text-primary">
            <x-loading />
            <span class="font-medium text-sm tracking-wide" aria-live="polite" x-text="overlayMessage"></span>
        </div>
        <span class="mt-2 text-[11px] text-gray-500">Please wait...</span>
    </div>

</div>
