<x-layouts.app title="Leave Type Settings" active="hr.leave-types.index">
    <x-slot:breadcrumbs>
        <span>HR workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Leave type settings</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Configuration"
        title="Leave type settings"
        description="Configure active leave types, attachment requirements, and annual quotas."
    >
        <x-slot:actions>
            <x-ui.button type="button" data-modal-open="create-leave-type">
                <x-icon name="plus" class="h-[17px] w-[17px]" />
                Add leave type
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <x-error-banner />

    <section class="overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
        @if ($leaveTypes->isEmpty())
            <div class="px-5 py-12 text-center">
                <span class="mx-auto mb-3.5 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                    <x-icon name="tag" class="h-6 w-6" />
                </span>
                <h3 class="text-sm font-bold text-neutral-900">No leave types configured</h3>
                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-neutral-600">
                    Create the first leave type to make it available in the employee request form.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Leave type</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Attachment</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Annual quota</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-[10px] font-bold uppercase tracking-wider text-neutral-600">Status</th>
                            <th class="whitespace-nowrap border-b border-neutral-300 bg-[#fbfcfc] px-3.5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-neutral-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaveTypes as $leaveType)
                            <tr class="border-b border-neutral-200 last:border-b-0">
                                <td class="px-3.5 py-3.5 align-middle">
                                    <strong class="block text-[13px] font-semibold text-neutral-900">{{ $leaveType->name }}</strong>
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs text-neutral-700">
                                    {{ $leaveType->requires_attachment ? 'Required' : 'Not required' }}
                                </td>
                                <td class="px-3.5 py-3.5 align-middle text-xs tabular-nums text-neutral-700">
                                    {{ $leaveType->default_annual_quota }} day(s)
                                </td>
                                <td class="px-3.5 py-3.5 align-middle">
                                    <form method="POST" action="{{ route('hr.leave-types.status', $leaveType) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $leaveType->is_active ? 0 : 1 }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-2 text-[11px] text-neutral-600 hover:text-neutral-900"
                                            title="{{ $leaveType->is_active ? 'Deactivate' : 'Activate' }} this leave type"
                                        >
                                            <span @class([
                                                'relative inline-block h-[17px] w-[30px] rounded-full transition',
                                                'bg-brand-600' => $leaveType->is_active,
                                                'bg-neutral-300' => ! $leaveType->is_active,
                                            ])>
                                                <span @class([
                                                    'absolute top-[3px] h-[11px] w-[11px] rounded-full bg-white',
                                                    'left-[16px]' => $leaveType->is_active,
                                                    'left-[3px]' => ! $leaveType->is_active,
                                                ])></span>
                                            </span>
                                            {{ $leaveType->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-3.5 py-3.5 text-right align-middle">
                                    <button
                                        type="button"
                                        data-modal-open="edit-leave-type-{{ $leaveType->id }}"
                                        class="text-xs font-bold text-brand-700 hover:underline"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Create leave type --}}
    <dialog id="create-leave-type" class="w-[92vw] max-w-md rounded-lg border border-neutral-300 bg-white p-0 text-left shadow-md backdrop:bg-neutral-900/40">
        <form method="POST" action="{{ route('hr.leave-types.store') }}">
            @csrf
            <div class="p-5">
                <h3 class="text-sm font-bold text-neutral-900">Add leave type</h3>

                <label for="create-name" class="mt-4 block text-[11px] font-bold text-neutral-900">Name <span class="text-red-700">*</span></label>
                <x-form.input id="create-name" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1.5 text-xs" />

                <label for="create-attachment" class="mt-3 block text-[11px] font-bold text-neutral-900">Attachment <span class="text-red-700">*</span></label>
                <x-form.select id="create-attachment" name="requires_attachment" required class="mt-1.5 text-xs">
                    <option value="0" @selected(old('requires_attachment') === '0')>Not required</option>
                    <option value="1" @selected(old('requires_attachment') === '1')>Required</option>
                </x-form.select>

                <label for="create-quota" class="mt-3 block text-[11px] font-bold text-neutral-900">Default annual quota (days) <span class="text-red-700">*</span></label>
                <x-form.input id="create-quota" name="default_annual_quota" type="number" min="0" value="{{ old('default_annual_quota') }}" required class="mt-1.5 text-xs" />
            </div>
            <div class="flex justify-end gap-2 border-t border-neutral-200 p-4">
                <button type="button" data-modal-close class="h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-900 hover:bg-neutral-100">Cancel</button>
                <button type="submit" class="h-9 rounded-md border border-brand-700 bg-brand-700 px-3 text-xs font-semibold text-white hover:bg-brand-600">Save leave type</button>
            </div>
        </form>
    </dialog>

    {{-- Edit leave type (one dialog per row) --}}
    @foreach ($leaveTypes as $leaveType)
        <dialog id="edit-leave-type-{{ $leaveType->id }}" class="w-[92vw] max-w-md rounded-lg border border-neutral-300 bg-white p-0 text-left shadow-md backdrop:bg-neutral-900/40">
            <form method="POST" action="{{ route('hr.leave-types.update', $leaveType) }}">
                @csrf
                @method('PUT')
                <div class="p-5">
                    <h3 class="text-sm font-bold text-neutral-900">Edit {{ $leaveType->name }}</h3>

                    <label for="edit-name-{{ $leaveType->id }}" class="mt-4 block text-[11px] font-bold text-neutral-900">Name <span class="text-red-700">*</span></label>
                    <x-form.input id="edit-name-{{ $leaveType->id }}" name="name" value="{{ $leaveType->name }}" required maxlength="255" class="mt-1.5 text-xs" />

                    <label for="edit-attachment-{{ $leaveType->id }}" class="mt-3 block text-[11px] font-bold text-neutral-900">Attachment <span class="text-red-700">*</span></label>
                    <x-form.select id="edit-attachment-{{ $leaveType->id }}" name="requires_attachment" required class="mt-1.5 text-xs">
                        <option value="0" @selected(! $leaveType->requires_attachment)>Not required</option>
                        <option value="1" @selected($leaveType->requires_attachment)>Required</option>
                    </x-form.select>

                    <label for="edit-quota-{{ $leaveType->id }}" class="mt-3 block text-[11px] font-bold text-neutral-900">Default annual quota (days) <span class="text-red-700">*</span></label>
                    <x-form.input id="edit-quota-{{ $leaveType->id }}" name="default_annual_quota" type="number" min="0" value="{{ $leaveType->default_annual_quota }}" required class="mt-1.5 text-xs" />

                    <label for="edit-active-{{ $leaveType->id }}" class="mt-3 block text-[11px] font-bold text-neutral-900">Status</label>
                    <x-form.select id="edit-active-{{ $leaveType->id }}" name="is_active" class="mt-1.5 text-xs">
                        <option value="1" @selected($leaveType->is_active)>Active</option>
                        <option value="0" @selected(! $leaveType->is_active)>Inactive</option>
                    </x-form.select>
                </div>
                <div class="flex justify-end gap-2 border-t border-neutral-200 p-4">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-900 hover:bg-neutral-100">Cancel</button>
                    <button type="submit" class="h-9 rounded-md border border-brand-700 bg-brand-700 px-3 text-xs font-semibold text-white hover:bg-brand-600">Save changes</button>
                </div>
            </form>
        </dialog>
    @endforeach
</x-layouts.app>
