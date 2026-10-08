@props([
    'leaveRequest',
])

<a
    href="{{ route('requests.show', $leaveRequest) }}"
    class="block border-b border-neutral-200 px-5 py-4 transition last:border-b-0 hover:bg-neutral-100"
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-[13px] font-semibold text-neutral-900">{{ $leaveRequest->user?->name }}</p>
            <p class="mt-1 text-[11px] text-neutral-600">
                {{ $leaveRequest->leaveType?->name }}
                ·
                {{ $leaveRequest->start_date->format('M j, Y') }}–{{ $leaveRequest->end_date->format('M j, Y') }}
                ({{ $leaveRequest->days_requested }} {{ Str::plural('day', $leaveRequest->days_requested) }})
            </p>
            <p class="mt-1.5 line-clamp-2 text-xs text-neutral-700">{{ $leaveRequest->reason }}</p>

            @if ($leaveRequest->attachments->isNotEmpty())
                <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-neutral-500">
                    <x-icon name="paperclip" class="h-3.5 w-3.5" />
                    {{ $leaveRequest->attachments->first()->file_name }}
                </p>
            @endif
        </div>

        <x-status-badge :status="$leaveRequest->status" class="flex-none" />
    </div>
</a>
