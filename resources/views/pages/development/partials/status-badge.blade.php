@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-600',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'pending_spv' => 'bg-amber-100 text-amber-700',
        'pending_leader' => 'bg-blue-100 text-blue-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'rejected_spv' => 'bg-red-100 text-red-700',
        'rejected_leader' => 'bg-red-100 text-red-700',
    ];

    $statusLabels = [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'pending_spv' => 'Pending SPV',
        'pending_leader' => 'Pending Leader',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'rejected_spv' => 'Rejected by SPV',
        'rejected_leader' => 'Rejected by Leader',
    ];
@endphp

<span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$status] ?? $statusColors['draft'] }}">
    {{ $statusLabels[$status] ?? 'Unknown' }}
</span>
