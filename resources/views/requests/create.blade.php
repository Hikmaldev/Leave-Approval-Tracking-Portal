<x-layouts.app title="New Request" active="requests.create">
    <x-slot:breadcrumbs>
        <span>Workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">New Request</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Employee workspace"
        title="New request"
        description="Provide the details below. Your request will first be reviewed by your direct supervisor."
    />

    <form
        method="POST"
        action="{{ route('requests.store') }}"
        enctype="multipart/form-data"
        data-request-form
        class="max-w-[820px] rounded-lg border border-neutral-300 bg-white p-7 shadow-sm"
    >
        @csrf

        <section>
            <h2 class="text-base font-bold text-neutral-900">Request details</h2>
            <p class="mt-1 text-xs text-neutral-600">Fields marked with <span class="text-red-700">*</span> are required.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <x-form.label for="leave_type_id" :required="true">Leave type</x-form.label>
                    <x-form.select id="leave_type_id" name="leave_type_id" required :invalid="$errors->has('leave_type_id')">
                        <option value="">Select a leave type</option>
                        @foreach ($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.error :messages="$errors->get('leave_type_id')" />
                    @if ($leaveTypes->isEmpty())
                        <p class="text-[11px] text-amber-800">No active leave types are configured yet. Ask HR to add one before submitting.</p>
                    @endif
                </div>

                <div class="space-y-1.5">
                    <x-form.label for="days_requested">Calculated days</x-form.label>
                    <x-form.input id="days_requested" type="text" value="—" readonly data-days-output class="bg-neutral-100 text-neutral-500" />
                    <p class="text-[11px] text-neutral-600">Calculated automatically from the date range.</p>
                </div>

                <div class="space-y-1.5">
                    <x-form.label for="start_date" :required="true">Start date</x-form.label>
                    <x-form.input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required :invalid="$errors->has('start_date')" />
                    <x-form.error :messages="$errors->get('start_date')" />
                </div>

                <div class="space-y-1.5">
                    <x-form.label for="end_date" :required="true">End date</x-form.label>
                    <x-form.input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required :invalid="$errors->has('end_date')" />
                    <x-form.error :messages="$errors->get('end_date')" />
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <x-form.label for="reason" :required="true">Reason</x-form.label>
                    <x-form.textarea
                        id="reason"
                        name="reason"
                        required
                        :invalid="$errors->has('reason')"
                        placeholder="Tell us why you are requesting this leave or permission."
                    >{{ old('reason') }}</x-form.textarea>
                    <x-form.error :messages="$errors->get('reason')" />
                </div>
            </div>
        </section>

        <section class="mt-7 border-t border-neutral-200 pt-7">
            <h2 class="text-base font-bold text-neutral-900">Attachment</h2>
            <p class="mt-1 text-xs text-neutral-600">Some leave types require supporting documentation (for example, a doctor's note).</p>

            <div class="mt-4 flex flex-col gap-3 rounded-md border border-dashed border-neutral-300 bg-[#fbfcfc] p-4 sm:flex-row sm:items-center">
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="upload" class="h-[18px] w-[18px]" />
                </span>
                <div class="min-w-0 flex-1">
                    <label for="attachment" class="block text-xs font-bold text-neutral-900">Choose a file to attach</label>
                    <p class="mt-0.5 text-[11px] text-neutral-600">Accepted types: PDF, JPG, PNG. Maximum size: 5 MB.</p>
                </div>
                <input
                    id="attachment"
                    name="attachment"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-xs text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-neutral-700 hover:file:bg-neutral-200 sm:w-auto"
                >
            </div>
            <x-form.error :messages="$errors->get('attachment')" class="mt-2" />
        </section>

        <section class="mt-7 border-t border-neutral-200 pt-7">
            <h2 class="text-base font-bold text-neutral-900">Balance preview</h2>

            <div class="mt-4 flex items-start gap-2.5 rounded-md border border-[#d7e5f2] bg-[#f1f6fb] px-3.5 py-3 text-xs leading-relaxed text-[#36526d]" data-balance-preview>
                <x-icon name="info" class="mt-px h-[18px] w-[18px] flex-none" />
                <div>Select a leave type and date range to preview how this request affects your balance.</div>
            </div>
        </section>

        <div class="mt-7 flex flex-col-reverse gap-2 border-t border-neutral-200 pt-6 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('requests.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit">Submit request</x-ui.button>
        </div>
    </form>

    @php
        $balanceData = $balances->mapWithKeys(fn ($balance) => [
            $balance->leave_type_id => [
                'quota' => $balance->quota,
                'used' => $balance->used,
                'remaining' => $balance->remainingDays(),
            ],
        ]);
    @endphp
    <script type="application/json" id="balance-data">{!! $balanceData->toJson() !!}</script>
</x-layouts.app>
