<div>
    {{-- Main Content: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Welcome Banner + My Courses --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Welcome Banner --}}
            <livewire:welcome-banner />

            {{-- My Courses --}}
            <livewire:my-courses />
        </div>

        {{-- Right Column: Calendar + Upcoming Schedules --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Calendar View (Mini) --}}
            <livewire:calendar-view :events="$calendarEvents" />

            {{-- Upcoming Schedules List (Filtered for Employee) --}}
            <livewire:upcoming-schedules :employeeId="auth()->id()" />
        </div>
    </div>
</div>
