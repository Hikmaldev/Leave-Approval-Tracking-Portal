<x-layouts.app title="All Requests" active="hr.requests.index">
    <x-slot:breadcrumbs>
        <span>HR workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">All requests</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Company-wide view"
        title="All requests"
        description="Filter and review leave requests across the company."
    >
        <x-slot:actions>
            <x-ui.button :href="route('hr.requests.export', $filters)" variant="secondary">
                Export CSV
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <x-error-banner />

    <section class="overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
        <form method="GET" action="{{ route('hr.requests.index') }}" class="border-b border-neutral-300 px-4.5 py-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex w-[160px] flex-col gap-1.5">
                    <label for="status" class="text-[10px] font-bold text-neutral-600">Status</label>
                    <select id="status" name="status" class="h-9 w-full rounded-md border border-neutral-300 bg-white px-2.5 text-xs text-neutral-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15">
                        <option value="">All statuses</option>
                        @foreach (\App\Enums\LeaveRequestStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex w-[220px] flex-col gap-1.5">
                    <label for="employee" class="text-[10px] font-bold text-neutral-600">Employee</label>
                    <x-form.input id="employee" name="employee" type="search" placeholder="Search employee" class="h-9 text-xs" :value="$filters['employee'] ?? null" />
                </div>

                <div class="flex w-[150px] flex-col gap-1.5">
                    <label for="from" class="text-[10px] font-bold text-neutral-600">From</label>
                    <x-form.input id="from" name="from" type="date" class="h-9 text-xs" :value="$filters['from'] ?? null" />
                </div>

                <div class="flex w-[150px] flex-col gap-1.5">
                    <label for="to" class="text-[10px] font-bold text-neutral-600">To</label>
                    <x-form.input id="to" name="to" type="date" class="h-9 text-xs" :value="$filters['to'] ?? null" />
                </div>

                <x-ui.button type="submit" variant="secondary" size="sm">Apply filters</x-ui.button>
            </div>
        </form>

        @if ($leaveRequests->isEmpty())
            <x-empty-state
                title="No requests to display"
                description="Requests matching the selected filters will appear here. No demo records are shown."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] border-collapse text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Employee</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Leave type</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Dates</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Days</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Status</th>
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
                                <td class="px-3.5 py-3.5 align-middle">
                                    <x-status-badge :status="$leaveRequest->status" />

                                    @php($rejection = $leaveRequest->approvalActions->firstWhere('decision', \App\Enums\ApprovalDecision::Rejected))
                                    @if ($rejection?->comment)
                                        <p class="mt-1 max-w-[240px] text-[11px] leading-snug text-neutral-600">{{ $rejection->comment }}</p>
                                    @endif
                                </td>
                                <td class="px-3.5 py-3.5 text-right align-middle">
                                    <a href="{{ route('requests.show', ['leaveRequest' => $leaveRequest, 'from' => 'hr']) }}" class="text-xs font-bold text-brand-700 hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
