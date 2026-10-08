<x-layouts.app title="Request Detail" active="requests.index">
    <x-slot:breadcrumbs>
        <a href="{{ route('requests.index') }}" class="font-semibold text-brand-700 hover:underline">My Requests</a>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Request detail</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Request detail"
        title="Request information"
        description="The request identified in the URL and its complete approval history."
    >
        @can('cancel', $leaveRequest)
            <x-slot:actions>
                <form method="POST" action="{{ route('requests.cancel', $leaveRequest) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="danger" size="sm">Cancel request</x-ui.button>
                </form>
            </x-slot:actions>
        @endcan
    </x-page-header>

    @php
        $rejection = $leaveRequest->approvalActions->firstWhere('decision', \App\Enums\ApprovalDecision::Rejected);
    @endphp

    @if ($rejection)
        <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-3.5 py-3 text-xs leading-relaxed text-red-800">
            <p class="font-bold">Rejected by {{ $rejection->actor?->name }} ({{ $rejection->step?->label() }})</p>
            @if ($rejection->comment)
                <p class="mt-1 text-sm leading-relaxed">{{ $rejection->comment }}</p>
            @endif
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">
        <div>
            <section class="rounded-lg border border-neutral-300 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-neutral-900">{{ $leaveRequest->leaveType?->name }}</h2>
                        <p class="mt-1 text-xs text-neutral-600">
                            {{ $leaveRequest->start_date->format('M j, Y') }}–{{ $leaveRequest->end_date->format('M j, Y') }}
                            · {{ $leaveRequest->days_requested }} {{ Str::plural('day', $leaveRequest->days_requested) }}
                        </p>
                    </div>
                    <x-status-badge :status="$leaveRequest->status" class="flex-none" />
                </div>

                <div class="mt-6 border-t border-neutral-200 pt-5">
                    <x-approval-chain :status="$leaveRequest->status" />
                </div>
            </section>

            <section class="mt-4 rounded-lg border border-neutral-300 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-neutral-900">Request details</h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Employee</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->user?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Leave type</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->leaveType?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Requested days</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->days_requested }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Submitted</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->created_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Start date</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->start_date->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">End date</dt>
                        <dd class="mt-1 text-[13px] text-neutral-900">{{ $leaveRequest->end_date->format('M j, Y') }}</dd>
                    </div>
                </dl>

                <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Reason</p>
                <p class="mt-1.5 rounded-md bg-neutral-100 px-3.5 py-3 text-[13px] leading-relaxed text-neutral-700">{{ $leaveRequest->reason }}</p>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-lg border border-neutral-300 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-neutral-900">Approval history</h2>

                @forelse ($leaveRequest->approvalActions as $action)
                    <div class="mt-4 border-t border-neutral-200 pt-4 first:mt-3 first:border-t-0 first:pt-0">
                        <p class="text-xs font-bold text-neutral-900">
                            {{ $action->decision?->label() }} by {{ $action->actor?->name }}
                            <span class="font-medium text-neutral-500">({{ $action->step?->label() }})</span>
                        </p>
                        <p class="mt-0.5 text-[11px] text-neutral-500">{{ $action->decided_at?->format('M j, Y g:i A') }}</p>
                        @if ($action->comment)
                            <p class="mt-2 text-sm leading-relaxed text-neutral-700">{{ $action->comment }}</p>
                        @endif
                    </div>
                @empty
                    <p class="mt-2 text-xs text-neutral-600">No approval actions yet. The request is waiting for its first decision.</p>
                @endforelse
            </section>

            <section class="rounded-lg border border-neutral-300 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-neutral-900">Attachment</h2>

                @forelse ($leaveRequest->attachments as $attachment)
                    <a
                        href="{{ route('attachments.download', $attachment) }}"
                        class="mt-3 flex items-center gap-1.5 text-xs text-brand-700 hover:underline"
                    >
                        <x-icon name="paperclip" class="h-3.5 w-3.5 flex-none" />
                        {{ $attachment->file_name }}
                    </a>
                @empty
                    <p class="mt-2 text-xs text-neutral-600">No attachment was provided.</p>
                @endforelse

                @can('addAttachment', $leaveRequest)
                    <form method="POST" action="{{ route('requests.attachments.store', $leaveRequest) }}" enctype="multipart/form-data" class="mt-4 border-t border-neutral-200 pt-4">
                        @csrf
                        <label for="attachment" class="block text-[11px] font-bold text-neutral-900">Add attachment</label>
                        <input
                            id="attachment"
                            name="attachment"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            class="mt-2 block w-full text-[11px] text-neutral-600 file:mr-2 file:rounded-md file:border-0 file:bg-neutral-100 file:px-2.5 file:py-1.5 file:text-[11px] file:font-semibold file:text-neutral-700"
                        >
                        <x-form.error :messages="$errors->get('attachment')" class="mt-1" />
                        <x-ui.button type="submit" size="sm" class="mt-2">Upload</x-ui.button>
                    </form>
                @endcan
            </section>

            <a class="inline-flex items-center gap-2 text-xs font-bold text-brand-700 hover:underline" href="{{ route('requests.index') }}">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Back to my requests
            </a>
        </aside>
    </div>
</x-layouts.app>
