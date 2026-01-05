@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-600',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'pending_spv' => 'bg-amber-100 text-amber-700',
        'pending_dept_head' => 'bg-amber-100 text-amber-700',
        'pending_leader' => 'bg-blue-100 text-blue-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'rejected_spv' => 'bg-red-100 text-red-700',
        'rejected_dept_head' => 'bg-red-100 text-red-700',
        'rejected_leader' => 'bg-red-100 text-red-700',
    ];

    $statusLabels = [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'pending_spv' => 'Waiting Supervisor Approval',
        'pending_dept_head' => 'Waiting Dept Head Approval',
        'pending_leader' => 'Waiting Section Head LID Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'rejected_spv' => 'Rejected by Supervisor',
        'rejected_dept_head' => 'Rejected by Dept Head',
        'rejected_leader' => 'Rejected by Section Head LID',
    ];
@endphp

<span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$status] ?? $statusColors['draft'] }}">
    {{ $statusLabels[$status] ?? 'Unknown' }}
</span>
