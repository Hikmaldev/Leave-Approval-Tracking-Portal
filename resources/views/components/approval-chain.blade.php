@props([
    'status',
])

@php
    use App\Enums\LeaveRequestStatus;

    $rejectedBySupervisor = $status === LeaveRequestStatus::RejectedBySupervisor;
    $rejectedByHr = $status === LeaveRequestStatus::RejectedByHr;
    $supervisorReached = in_array($status, [LeaveRequestStatus::PendingHr, LeaveRequestStatus::Approved, LeaveRequestStatus::RejectedByHr, LeaveRequestStatus::RejectedBySupervisor], true);
    $hrReached = in_array($status, [LeaveRequestStatus::Approved, LeaveRequestStatus::RejectedByHr], true);
    $fullyApproved = $status === LeaveRequestStatus::Approved;

    $markerClasses = function (string $state): string {
        return match ($state) {
            'done' => 'border-brand-700 bg-brand-700 text-white',
            'rejected' => 'border-red-600 bg-red-600 text-white',
            default => 'border-neutral-300 bg-white text-neutral-500',
        };
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start']) }} role="list" aria-label="Approval chain">
    {{-- Submitted --}}
    <div class="flex flex-1 flex-col items-center gap-2 text-center" role="listitem">
        <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[11px] font-bold {{ $markerClasses('done') }}">1</span>
        <span class="text-[11px] font-semibold text-brand-700">Submitted</span>
    </div>

    <span class="mt-[13px] h-0.5 flex-1 {{ $supervisorReached || $rejectedBySupervisor ? 'bg-brand-700' : 'bg-neutral-300' }}"></span>

    {{-- Supervisor --}}
    <div class="flex flex-1 flex-col items-center gap-2 text-center" role="listitem">
        <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[11px] font-bold {{ $markerClasses($rejectedBySupervisor ? 'rejected' : ($supervisorReached ? 'done' : 'pending')) }}">2</span>
        <span class="text-[11px] font-semibold {{ $rejectedBySupervisor ? 'text-red-700' : ($supervisorReached ? 'text-brand-700' : 'text-neutral-500') }}">Supervisor</span>
    </div>

    @unless ($rejectedBySupervisor)
        <span class="mt-[13px] h-0.5 flex-1 {{ $hrReached || $rejectedByHr ? 'bg-brand-700' : 'bg-neutral-300' }}"></span>
    @endunless

    {{-- HR --}}
    @unless ($rejectedBySupervisor)
        <div class="flex flex-1 flex-col items-center gap-2 text-center" role="listitem">
            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[11px] font-bold {{ $markerClasses($rejectedByHr ? 'rejected' : ($hrReached ? 'done' : 'pending')) }}">3</span>
            <span class="text-[11px] font-semibold {{ $rejectedByHr ? 'text-red-700' : ($hrReached ? 'text-brand-700' : 'text-neutral-500') }}">HR</span>
        </div>

        <span class="mt-[13px] h-0.5 flex-1 {{ $fullyApproved ? 'bg-brand-700' : 'bg-neutral-300' }}"></span>

        {{-- Final --}}
        <div class="flex flex-1 flex-col items-center gap-2 text-center" role="listitem">
            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[11px] font-bold {{ $markerClasses($fullyApproved ? 'done' : 'pending') }}">4</span>
            <span class="text-[11px] font-semibold {{ $fullyApproved ? 'text-brand-700' : 'text-neutral-500' }}">Final</span>
        </div>
    @endunless
</div>
