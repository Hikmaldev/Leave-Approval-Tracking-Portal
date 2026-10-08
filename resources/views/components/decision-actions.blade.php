@props([
    'leaveRequest',
    'action',
])

@php
    $approveId = 'approve-'.$leaveRequest->id;
    $rejectId = 'reject-'.$leaveRequest->id;
    $statusLabel = $leaveRequest->status->label();
@endphp

<div class="flex justify-end gap-2">
    <button
        type="button"
        data-modal-open="{{ $approveId }}"
        class="h-8 rounded-md border border-brand-700 bg-brand-700 px-3 text-[11px] font-bold text-white transition hover:bg-brand-600 hover:border-brand-600"
    >
        Approve
    </button>
    <button
        type="button"
        data-modal-open="{{ $rejectId }}"
        class="h-8 rounded-md border border-red-300 bg-white px-3 text-[11px] font-bold text-red-700 transition hover:bg-red-50"
    >
        Reject
    </button>
</div>

{{-- Approve confirmation (design system 4: elevation-2 modal, not inline). --}}
<dialog id="{{ $approveId }}" class="w-[92vw] max-w-md rounded-lg border border-neutral-300 bg-white p-0 text-left shadow-md backdrop:bg-neutral-900/40">
    <form method="POST" action="{{ route($action, $leaveRequest) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="decision" value="approved">

        <div class="p-5">
            <h3 class="text-sm font-bold text-neutral-900">Approve {{ $leaveRequest->user?->name }}'s request?</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-neutral-600">
                {{ $leaveRequest->leaveType?->name }} ·
                {{ $leaveRequest->start_date->format('M j, Y') }}–{{ $leaveRequest->end_date->format('M j, Y') }}
                ({{ $leaveRequest->days_requested }} day(s)).
            </p>
            <p class="mt-2 text-[11px] leading-relaxed text-neutral-500">
                Current status: {{ $statusLabel }}. Approving moves the request to the next step.
            </p>

            <label for="{{ $approveId }}-comment" class="mt-4 block text-[11px] font-bold text-neutral-900">Comment (optional)</label>
            <textarea
                id="{{ $approveId }}-comment"
                name="comment"
                rows="2"
                maxlength="2000"
                class="mt-1.5 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15"
                placeholder="Add an optional note for the record."
            ></textarea>
        </div>

        <div class="flex justify-end gap-2 border-t border-neutral-200 p-4">
            <button type="button" data-modal-close class="h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-900 hover:bg-neutral-100">Cancel</button>
            <button type="submit" class="h-9 rounded-md border border-brand-700 bg-brand-700 px-3 text-xs font-semibold text-white hover:bg-brand-600">Approve</button>
        </div>
    </form>
</dialog>

{{-- Reject confirmation (comment is required, FR-APR-02/04). --}}
<dialog id="{{ $rejectId }}" class="w-[92vw] max-w-md rounded-lg border border-neutral-300 bg-white p-0 text-left shadow-md backdrop:bg-neutral-900/40">
    <form method="POST" action="{{ route($action, $leaveRequest) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="decision" value="rejected">

        <div class="p-5">
            <h3 class="text-sm font-bold text-neutral-900">Reject {{ $leaveRequest->user?->name }}'s request?</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-neutral-600">
                {{ $leaveRequest->leaveType?->name }} ·
                {{ $leaveRequest->start_date->format('M j, Y') }}–{{ $leaveRequest->end_date->format('M j, Y') }}
                ({{ $leaveRequest->days_requested }} day(s)).
            </p>

            <label for="{{ $rejectId }}-comment" class="mt-4 block text-[11px] font-bold text-neutral-900">Rejection reason <span class="text-red-700">*</span></label>
            <textarea
                id="{{ $rejectId }}-comment"
                name="comment"
                rows="3"
                maxlength="2000"
                required
                class="mt-1.5 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15"
                placeholder="Explain why this request is being rejected. The employee will see this."
            ></textarea>
        </div>

        <div class="flex justify-end gap-2 border-t border-neutral-200 p-4">
            <button type="button" data-modal-close class="h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-900 hover:bg-neutral-100">Cancel</button>
            <button type="submit" class="h-9 rounded-md border border-red-300 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50">Reject</button>
        </div>
    </form>
</dialog>
