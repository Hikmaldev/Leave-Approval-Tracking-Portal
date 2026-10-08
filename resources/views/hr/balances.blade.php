<x-layouts.app title="Balance Management" active="hr.balances.index">
    <x-slot:breadcrumbs>
        <span>HR workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Balance management</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Employee balances"
        title="Balance management"
        description="Review employee balances and make adjustments with a required reason."
    />

    <x-error-banner />

    <div class="flex items-start gap-2.5 rounded-md border border-[#f3dfaa] bg-[#fff8e6] px-3.5 py-3 text-xs leading-relaxed text-[#7c4a03]">
        <x-icon name="info" class="mt-px h-[17px] w-[17px] flex-none" />
        <span>Balance adjustments are audit-sensitive. Every saved adjustment includes a reason and is recorded in the balance adjustment history.</span>
    </div>

    <section class="mt-5 overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
        <form method="GET" action="{{ route('hr.balances.index') }}" class="border-b border-neutral-300 px-4.5 py-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex w-[240px] flex-col gap-1.5">
                    <label for="employee-search" class="text-[10px] font-bold text-neutral-600">Employee</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-neutral-500" />
                        <x-form.input id="employee-search" name="employee" type="search" placeholder="Search employee" class="h-9 pl-8 text-xs" :value="$filters['employee'] ?? null" />
                    </div>
                </div>

                <div class="flex w-[160px] flex-col gap-1.5">
                    <label for="year" class="text-[10px] font-bold text-neutral-600">Year</label>
                    <select id="year" name="year" class="h-9 w-full rounded-md border border-neutral-300 bg-white px-2.5 text-xs text-neutral-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15">
                        <option value="{{ $year }}">{{ $year }}</option>
                    </select>
                </div>

                <x-ui.button type="submit" variant="secondary" size="sm">Search</x-ui.button>
            </div>
        </form>

        @if ($balances->isEmpty())
            <x-empty-state
                title="No employee balances to display"
                description="Balance records appear once leave types are configured. No demo balances are shown."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Employee</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Leave type</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Year</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Quota</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Used</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Remaining</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-neutral-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balances as $balance)
                            <tr class="border-b border-neutral-200 last:border-b-0">
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    <strong class="block text-xs font-semibold text-neutral-900">{{ $balance->user?->name }}</strong>
                                    @if ($balance->user?->department)
                                        <span class="mt-0.5 block text-[10px] text-neutral-500">{{ $balance->user->department }}</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">{{ $balance->leaveType?->name }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums text-neutral-700">{{ $balance->year }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums text-neutral-700">{{ $balance->quota }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums text-neutral-700">{{ $balance->used }}</td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums font-semibold text-neutral-900">{{ $balance->remainingDays() }}</td>
                                <td class="px-3.5 py-3.5 text-right align-middle">
                                    <button
                                        type="button"
                                        data-modal-open="adjust-balance-{{ $balance->id }}"
                                        class="h-8 rounded-md border border-neutral-300 bg-white px-3 text-[11px] font-bold text-neutral-700 hover:bg-neutral-100"
                                    >
                                        Adjust
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @foreach ($balances as $balance)
        <dialog id="adjust-balance-{{ $balance->id }}" class="w-[92vw] max-w-md rounded-lg border border-neutral-300 bg-white p-0 text-left shadow-md backdrop:bg-neutral-900/40">
            <form method="POST" action="{{ route('hr.balances.update', ['user' => $balance->user, 'leaveType' => $balance->leaveType]) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="year" value="{{ $balance->year }}">

                <div class="p-5">
                    <h3 class="text-sm font-bold text-neutral-900">Adjust balance</h3>
                    <p class="mt-1 text-xs text-neutral-600">
                        {{ $balance->user?->name }} · {{ $balance->leaveType?->name }} · {{ $balance->year }}
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <label for="quota-{{ $balance->id }}" class="block text-[11px] font-bold text-neutral-900">Quota</label>
                            <x-form.input id="quota-{{ $balance->id }}" name="quota" type="number" min="0" value="{{ $balance->quota }}" class="mt-1.5 text-xs" />
                        </div>
                        <div>
                            <label for="used-{{ $balance->id }}" class="block text-[11px] font-bold text-neutral-900">Used</label>
                            <x-form.input id="used-{{ $balance->id }}" name="used" type="number" min="0" value="{{ $balance->used }}" class="mt-1.5 text-xs" />
                        </div>
                    </div>

                    <label for="reason-{{ $balance->id }}" class="mt-3 block text-[11px] font-bold text-neutral-900">Reason <span class="text-red-700">*</span></label>
                    <textarea
                        id="reason-{{ $balance->id }}"
                        name="reason"
                        rows="2"
                        maxlength="500"
                        required
                        class="mt-1.5 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15"
                        placeholder="Why is this balance being adjusted? This is recorded for audit."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-neutral-200 p-4">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-900 hover:bg-neutral-100">Cancel</button>
                    <button type="submit" class="h-9 rounded-md border border-brand-700 bg-brand-700 px-3 text-xs font-semibold text-white hover:bg-brand-600">Save adjustment</button>
                </div>
            </form>
        </dialog>
    @endforeach
</x-layouts.app>
