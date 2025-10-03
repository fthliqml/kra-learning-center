<div class="space-y-8">
    <h2 class="text-xl font-semibold">Test Configuration</h2>

    <div class="grid md:grid-cols-2 gap-8">
        <!-- Pretest Settings -->
        <div class="card bg-base-100 shadow-sm border border-base-400">
            <div class="card-body space-y-4">
                <h3 class="font-medium flex items-center gap-2"><x-icon name="o-clipboard-document-list" class="size-5" />
                    Pretest</h3>
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-input label="Passing Score" placeholder="0-100" type="number" min="0" max="100"
                            wire:model.live.debounce.400ms="pretest_passing_score" inputmode="numeric" pattern="[0-9]*"
                            x-on:input="if(!this.value)return; const v=this.value; if(v.length>3) this.value=v.slice(0,3); const n=parseInt(this.value); if(n>100) this.value=100; if(n<0) this.value=0;"
                            class="w-28" />
                        <x-input label="Max Attempts" placeholder="∞" type="number" min="1"
                            wire:model.live.debounce.400ms="pretest_max_attempts" class="w-32"
                            x-on:input="if(!this.value){return;} const v=this.value; if(v.length>3) this.value=v.slice(0,3); const n=parseInt(this.value); if(isNaN(n) || n<1) this.value='';" />
                        <div class="flex items-center gap-3 pt-9">
                            <input type="checkbox" class="toggle toggle-sm"
                                wire:model.live="pretest_randomize_question" />
                            <span class="text-xs font-medium">Randomize</span>
                        </div>
                    </div>
                    <p class="text-xs text-base-content/60 leading-snug">
                        Max Attempts dikosongkan apabila ingin tidak ada batas.
                    </p>
                </div>
            </div>
        </div>

        <!-- Post Test Settings -->
        <div class="card bg-base-100 shadow-sm border border-base-400">
            <div class="card-body space-y-4">
                <h3 class="font-medium flex items-center gap-2"><x-icon name="o-check-badge" class="size-5" /> Post Test
                </h3>
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-input label="Passing Score" placeholder="0-100" type="number" min="0" max="100"
                            wire:model.live.debounce.400ms="posttest_passing_score" inputmode="numeric" pattern="[0-9]*"
                            x-on:input="if(!this.value)return; const v=this.value; if(v.length>3) this.value=v.slice(0,3); const n=parseInt(this.value); if(n>100) this.value=100; if(n<0) this.value=0;"
                            class="w-28" />
                        <x-input label="Max Attempts" placeholder="∞" type="number" min="1"
                            wire:model.live.debounce.400ms="posttest_max_attempts" class="w-32"
                            x-on:input="if(!this.value){return;} const v=this.value; if(v.length>3) this.value=v.slice(0,3); const n=parseInt(this.value); if(isNaN(n) || n<1) this.value='';" />
                        <div class="flex items-center gap-3 pt-9">
                            <input type="checkbox" class="toggle toggle-sm"
                                wire:model.live="posttest_randomize_question" />
                            <span class="text-xs font-medium">Randomize</span>
                        </div>
                    </div>
                    <p class="text-xs text-base-content/60 leading-snug">
                        Randomize akan mengacak urutan soal.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-base-300/50">
        <div class="flex items-center gap-4">
            <x-ui.save-draft-status action="saveDraft" :dirty="$isDirty ?? false" :ever="$hasEverSaved ?? false" :persisted="$persisted ?? false" />
        </div>
        <div class="flex gap-2 ml-auto">
            <x-ui.button type="button" variant="primary" class="gap-2" wire:click="goBack">
                <x-icon name="o-arrow-left" class="size-4" />
                <span>Back</span>
            </x-ui.button>
            <x-ui.button type="button" class="gap-2 hover:bg-base-300" wire:click="finish">
                <x-icon name="o-check" class="size-4" />
                <span>Finish</span>
            </x-ui.button>
        </div>
    </div>
</div>
