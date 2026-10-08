@props([
    'status',
])

@php
    use App\Enums\LeaveRequestStatus;

    $classes = match ($status) {
        LeaveRequestStatus::PendingSupervisor => 'bg-amber-100 text-amber-800',
        LeaveRequestStatus::PendingHr => 'bg-blue-100 text-blue-800',
        LeaveRequestStatus::Approved => 'bg-green-100 text-green-800',
        LeaveRequestStatus::RejectedBySupervisor, LeaveRequestStatus::RejectedByHr => 'bg-red-100 text-red-800',
        LeaveRequestStatus::Cancelled => 'bg-gray-100 text-gray-600',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wide '.$classes]) }}>
    {{ $status->label() }}
</span>
