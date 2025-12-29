@props(['status'])

@php
    $config = [
        'completed' => [
            'class' => 'bg-emerald-100 text-emerald-700',
            'label' => 'Completed',
        ],
        'scheduled' => [
            'class' => 'bg-blue-100 text-blue-700',
            'label' => 'Scheduled',
        ],
        'waiting' => [
            'class' => 'bg-amber-100 text-amber-700',
            'label' => 'Waiting',
        ],
    ];

    $statusConfig = $config[$status] ?? $config['waiting'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $statusConfig['class'] }}">
    {{ $statusConfig['label'] }}
</span>
