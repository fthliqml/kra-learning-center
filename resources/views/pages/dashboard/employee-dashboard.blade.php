<div>
    {{-- Main Content: 2 Columns Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Welcome Banner + My Courses + Pending Surveys --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Welcome Banner --}}
            <livewire:components.dashboard.welcome-banner />

            {{-- My Courses --}}
            <livewire:components.dashboard.my-courses />

            {{-- Pending Surveys --}}
            <livewire:components.dashboard.pending-surveys />
        </div>

        {{-- Right Column: Calendar + Upcoming Schedules --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Calendar View (Mini) --}}
            <livewire:components.dashboard.calendar-view :events="$calendarEvents" />

            {{-- Upcoming Schedules List (Filtered for Employee) --}}
            <livewire:components.dashboard.upcoming-schedules :employeeId="auth()->id()" />
        </div>
    </div>
</div>
