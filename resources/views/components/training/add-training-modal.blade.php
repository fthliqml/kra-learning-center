{{-- resources/views/livewire/training-modal.blade.php --}}
<div>
    <!-- Trigger Button -->
    <x-ui.button wire:click="openModal" variant="primary">
        <x-icon name="o-plus" class="w-5 h-5" />
        Add New Training
    </x-ui.button>

    <!-- Modal -->
    <x-modal wire:model="showModal" title="Buat Training Baru" box-class="backdrop-blur max-w-4xl">
        <div class="space-y-6">
            <!-- Tabs Navigation -->
            <x-tabs wire:model="activeTab">
                <x-tab name="training" label="Training Config" icon="o-academic-cap">
                    <div class="space-y-6 py-4">
                        <!-- Nama Training -->
                        <x-input wire:model="nama_training" label="Training Name" placeholder="Masukkan nama training"
                            class="input-bordered" />

                        <!-- Date Range -->
                        <x-datepicker wire:model.defer="date" placeholder="Select date range" icon="o-calendar"
                            class="w-full" label="Tanggal Training" :config="[
                                'mode' => 'range',
                                'altInput' => true,
                                'altFormat' => 'd M Y',
                            ]" />


                        <!-- Tipe Instruktur -->
                        <div class="space-y-3">
                            <x-choices label="Tipe Instruktur" wire:model="tipe_instruktur" :options="[
                                ['id' => 'internal', 'name' => 'Instruktur Internal'],
                                ['id' => 'eksternal', 'name' => 'Instruktur Eksternal'],
                            ]"
                                option-value="id" option-label="name" single />

                            @if ($tipe_instruktur === 'internal')
                                <x-choices label="Pilih Instruktur Internal" wire:model="instruktur_internal"
                                    :options="$internal_instructors" option-value="id" option-label="name"
                                    placeholder="Pilih instruktur..." single />
                            @else
                                <x-input wire:model="instruktur_eksternal" label="Nama Instruktur Eksternal"
                                    placeholder="Masukkan nama instruktur eksternal" />
                            @endif
                        </div>

                        <!-- Participants Section -->
                        <x-choices label="Searchable + Single + Debounce + Min chars" wire:model="participants"
                            search-function="userSearch" debounce="300ms" {{-- Default is `250ms` --}} min-chars="2"
                            {{-- Default is `0` --}} hint="Type at least 2 chars" searchable />
                    </div>
                </x-tab>

                <x-tab name="session" label="Sesi Config" icon="o-cog-6-tooth">
                    <div class="space-y-6 py-4">
                        <!-- Room Selection -->
                        <div class="space-y-3">
                            <x-choices label="Pilih Ruangan" wire:model="selected_room" :options="$available_rooms"
                                option-value="id" option-label="name" placeholder="Pilih ruangan..." single>
                                {{-- <x-slot:prepend-item>
                                    <div class="font-bold p-2 border-b">Daftar Ruangan</div>
                                </x-slot:prepend-item>
                                <x-slot:item="{ item }">
                                <div class="flex flex-col">
                                    <div class="font-medium">{{ $item['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $item['location'] }}</div>
                                </div>
                                </x-slot:item> --}}
                            </x-choices>

                            @if ($selected_room_location)
                                <div class="text-sm text-gray-600 dark:text-gray-400 pl-1">
                                    <x-icon name="o-map-pin" class="w-4 h-4 inline mr-1" />
                                    Lokasi: {{ $selected_room_location }}
                                </div>
                            @endif
                        </div>

                        <!-- Session Type -->
                        <x-choices label="Pengaturan Sesi" wire:model="session_type" :options="[
                            ['id' => 'same_room', 'name' => 'Ruangan yang sama untuk semua hari'],
                            ['id' => 'different_room', 'name' => 'Ruangan berbeda per hari'],
                        ]" option-value="id"
                            option-label="name" single />

                        <!-- Session Instructor -->
                        <x-input wire:model="session_instructor" label="Instruktur Sesi"
                            placeholder="Nama instruktur untuk sesi ini"
                            hint="Instruktur yang akan memimpin sesi training" />

                        <!-- Sessions Preview -->
                        @if (count($sessions) > 0)
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <h5 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                    <x-icon name="o-eye" class="w-4 h-4 mr-2" />
                                    Preview Sesi Training
                                </h5>
                                <div class="space-y-3">
                                    @foreach ($sessions as $index => $session)
                                        <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                                                        <x-icon name="o-calendar"
                                                            class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-sm">
                                                            @if ($session_type === 'same_room')
                                                                Sesi: {{ $session['date_range'] }}
                                                            @else
                                                                Sesi {{ $index + 1 }}: {{ $session['date'] }}
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $session['room_name'] }} -
                                                            {{ $session['room_location'] }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <x-badge value="Sesi {{ $index + 1 }}"
                                                    class="badge-primary badge-sm" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </x-tab>
            </x-tabs>
        </div>

        <!-- Modal Actions -->
        <x-slot:actions>
            <x-button label="Batal" wire:click="closeModal" class="btn-ghost" />
            <x-button label="Simpan Training" wire:click="saveTraining" class="btn-primary" spinner="saveTraining" />
        </x-slot:actions>
    </x-modal>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="alert alert-success mt-4">
            <x-icon name="o-check-circle" class="w-6 h-6" />
            {{ session('message') }}
        </div>
    @endif
</div>

@push('scripts')
    <script>
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                @this.call('hideSuggestions');
            }
        });

        document.addEventListener('livewire:load', function() {
            Livewire.on('trainingCreated', () => {
                console.log('Training created successfully!');
            });
        });
    </script>
@endpush
