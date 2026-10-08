<x-layouts.app title="Supervisor Approval Queue" active="approvals.supervisor">
    <x-slot:breadcrumbs>
        <span>Approval workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Supervisor queue</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Supervisor review"
        title="Approval queue"
        description="Review requests from your direct reports before they move to HR."
    />

    <x-error-banner />

    <section class="overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-neutral-300 px-4.5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold text-neutral-900">Waiting for Supervisor</p>
                <p class="mt-0.5 text-[11px] text-neutral-600">Only requests assigned to your supervisor relationship are shown.</p>
            </div>
            <span class="self-start rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-800 sm:self-auto">Waiting for Supervisor</span>
        </div>

        @if ($leaveRequests->isEmpty())
            <x-empty-state
                title="No requests waiting for your approval"
                description="New requests from your assigned direct reports will appear here."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Employee</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Leave type</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Dates</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Days</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Reason</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Attachment</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-neutral-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaveRequests as $leaveRequest)
                            <tr class="border-b border-neutral-200 last:border-b-0">
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    <strong class="block text-xs font-semibold text-neutral-900">{{ $leaveRequest->user?->name }}</strong>
                                    @if ($leaveRequest->user?->department)
                                        <span class="mt-0.5 block text-[10px] text-neutral-500">{{ $leaveRequest->user->department }}</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">{{ $leaveRequest->leaveType?->name }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    {{ $leaveRequest->start_date->format('M j, Y') }} – {{ $leaveRequest->end_date->format('M j, Y') }}
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums text-neutral-700">{{ $leaveRequest->days_requested }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    <span class="line-clamp-2 max-w-[220px]">{{ $leaveRequest->reason }}</span>
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    @if ($leaveRequest->attachments->isNotEmpty())
                                        <a
                                            href="{{ route('attachments.download', $leaveRequest->attachments->first()) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 hover:underline"
                                        >
                                            <x-icon name="paperclip" class="h-3.5 w-3.5 flex-none" />
                                            {{ $leaveRequest->attachments->first()->file_name }}
                                        </a>
                                    @else
                                        <span class="text-[11px] text-neutral-500">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-3.5 align-middle">
                                    <x-decision-actions :leave-request="$leaveRequest" action="approvals.supervisor.decide" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
