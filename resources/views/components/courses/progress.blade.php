@props(['progress' => 0])
<div>
    <div class="h-2 w-full rounded-full bg-gray-200/80 overflow-hidden" role="progressbar" aria-label="Course progress"
        aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}"
        aria-valuetext="{{ $progress }} percent">
        <div class="h-full bg-primary rounded-full transition-all" style="width: {{ $progress }}%"></div>
    </div>
    <div class="mt-1 text-xs text-gray-600 flex justify-between">
        <span>{{ $progress != 100 ? 'In Progress' : 'Completed' }}</span>
        <span>{{ $progress }}%</span>
    </div>
</div>
