<div class="w-full rounded-2xl shadow-md p-8 bg-gradient-to-r from-primary to-tetriary text-white">
    <div class="flex flex-col items-center text-center md:items-start md:text-left">
        {{-- Greeting --}}
        <p class="text-sm">Hi, {{ $userName }}</p>
        <h1 class="text-3xl font-bold mt-1">Welcome Back!</h1>

        {{-- Stats Message --}}
        <div class="mt-4 text-sm leading-relaxed">
            <p>You've completed <span class="font-semibold">{{ $completedCoursesThisMonth }}</span>
                {{ Str::plural('course', $completedCoursesThisMonth) }} this month</p>
            <p class="mt-1">Keep it up!</p>
        </div>
    </div>
</div>
