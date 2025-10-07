<div class="space-y-6">
    <!-- Name -->
    <div class="p-4 border rounded-md">
        <div class="flex items-start gap-2">
            <x-icon name="o-academic-cap" class="w-4 h-4 text-primary/70 mt-0.5" />
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Training Name</p>
                <p class="font-semibold text-gray-800 break-words mt-1">{{ $name }}</p>
            </div>
        </div>
    </div>

    <!-- Date Range -->
    <div class="p-4 border rounded-md">
        <div class="flex items-start gap-2">
            <x-icon name="o-calendar" class="w-4 h-4 text-primary/70 mt-0.5" />
            <div class="flex-1">
                <p class="text-xs uppercase tracking-wide text-gray-500">Date Range</p>
                <p class="font-semibold text-gray-800 mt-1">{{ $this->formattedRange ?: $dateRange }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Trainer -->
        <div class="p-4 border rounded-md">
            <div class="flex items-start gap-2">
                <x-icon name="o-user" class="w-4 h-4 text-primary/70 mt-0.5" />
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Trainer (Day {{ $dayNumber }})</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $trainer_name ?: '—' }}</p>
                </div>
            </div>
        </div>
        <!-- Group Competency -->
        <div class="p-4 border rounded-md">
            <div class="flex items-start gap-2">
                <x-icon name="o-squares-2x2" class="w-4 h-4 text-primary/70 mt-0.5" />
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Group Competency</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $group_comp ?: '—' }}</p>
                </div>
            </div>
        </div>
        <!-- Room / Location -->
        <div class="p-4 border rounded-md">
            <div class="flex items-start gap-2">
                <x-icon name="o-map-pin" class="w-4 h-4 text-primary/70 mt-0.5" />
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Room / Location</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $room_name ?: '—' }}<br><span
                            class="text-xs text-gray-500">{{ $room_location }}</span></p>
                </div>
            </div>
        </div>
        <!-- Time -->
        <div class="p-4 border rounded-md">
            <div class="flex items-start gap-2">
                <x-icon name="o-clock" class="w-4 h-4 text-primary/70 mt-0.5" />
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Time</p>
                    <p class="font-semibold text-gray-800 mt-1">{{ $start_time ? substr($start_time, 0, 5) : '—' }} -
                        {{ $end_time ? substr($end_time, 0, 5) : '—' }}<br><span
                            class="text-xs text-gray-500">WITA</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
