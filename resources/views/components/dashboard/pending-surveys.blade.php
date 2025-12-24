<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <x-icon name="o-clipboard-document-list" class="w-5 h-5 text-primary" />
            Pending Surveys
        </h2>
        @if (count($surveys) > 0)
            <a href="{{ route('survey.index', ['level' => 1]) }}"
                class="text-sm text-primary hover:text-primary/80 font-medium">
                View All →
            </a>
        @endif
    </div>

    @if (count($surveys) > 0)
        <div class="space-y-3">
            @foreach ($surveys as $survey)
                <a href="{{ route('survey.take', ['level' => $survey['level'], 'surveyId' => $survey['id']]) }}"
                    class="block p-3 rounded-lg border border-gray-100 hover:border-primary/30 hover:bg-primary/5 transition-all duration-200 group">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-gray-800 text-sm truncate group-hover:text-primary transition-colors"
                                title="{{ $survey['training_name'] }}">
                                {{ $survey['training_name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Level {{ $survey['level'] }} Survey • {{ $survey['created_at'] }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            @if ($survey['status'] === 'incomplete')
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">
                                    In Progress
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                    Not Started
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if (count($surveys) >= 5)
            <p class="text-xs text-gray-500 text-center mt-3">
                Showing {{ count($surveys) }} pending surveys
            </p>
        @endif
    @else
        <div class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-green-50 flex items-center justify-center">
                <x-icon name="o-check-circle" class="w-8 h-8 text-green-500" />
            </div>
            <h3 class="text-sm font-medium text-gray-700 mb-1">All Caught Up!</h3>
            <p class="text-xs text-gray-500">
                You have no pending surveys to complete.
            </p>
        </div>
    @endif
</div>
