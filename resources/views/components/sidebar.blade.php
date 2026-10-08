@props([
    'active' => null,
])

@php
    $user = auth()->user();
@endphp

<aside class="z-20 flex w-full flex-col gap-3 border-b border-neutral-300 bg-white px-4 py-3 lg:fixed lg:inset-y-0 lg:left-0 lg:w-[248px] lg:gap-0 lg:border-b-0 lg:border-r lg:px-4 lg:pb-5 lg:pt-7">
    <div class="flex items-center gap-3 px-1 lg:mb-11 lg:px-3">
        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand-700 text-white">
            <x-icon name="document" class="h-5 w-5" />
        </span>
        <span>
            <span class="block text-[15px] font-bold leading-tight tracking-tight">Leave Portal</span>
            <span class="block text-[11px] text-neutral-600">Approval tracking</span>
        </span>
    </div>

    <nav class="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:pb-0" aria-label="Primary navigation">
        <p class="hidden px-3 pb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-neutral-500 lg:block">Workspace</p>

        <x-nav-link :href="route('dashboard')" icon="dashboard" :active="$active === 'dashboard'">Dashboard</x-nav-link>
        <x-nav-link :href="route('requests.create')" icon="plus" :active="$active === 'requests.create'">New Request</x-nav-link>
        <x-nav-link :href="route('requests.index')" icon="document" :active="$active === 'requests.index'">My Requests</x-nav-link>
        <x-nav-link :href="route('balances.index')" icon="wallet" :active="$active === 'balances.index'">Leave Balance</x-nav-link>

        @if ($user?->isSupervisor() || $user?->isHrAdmin())
            <p class="hidden px-3 pb-2 pt-6 text-[11px] font-bold uppercase tracking-[0.1em] text-neutral-500 lg:block">Approval workspace</p>
            <x-nav-link :href="route('approvals.supervisor')" icon="check" :active="$active === 'approvals.supervisor'">Supervisor Queue</x-nav-link>
        @endif

        @if ($user?->isHrAdmin())
            <p class="hidden px-3 pb-2 pt-6 text-[11px] font-bold uppercase tracking-[0.1em] text-neutral-500 lg:block">HR workspace</p>
            <x-nav-link :href="route('approvals.hr')" icon="check" :active="$active === 'approvals.hr'">HR Queue</x-nav-link>
            <x-nav-link :href="route('hr.requests.index')" icon="document" :active="$active === 'hr.requests.index'">All Requests</x-nav-link>
            <x-nav-link :href="route('hr.leave-types.index')" icon="tag" :active="$active === 'hr.leave-types.index'">Leave Types</x-nav-link>
            <x-nav-link :href="route('hr.balances.index')" icon="wallet" :active="$active === 'hr.balances.index'">Balance Management</x-nav-link>
        @endif
    </nav>

    <div class="mt-auto hidden lg:block">
        <div class="rounded-lg border border-[#dbeae2] bg-[#f8faf9] px-3.5 py-4">
            <span class="mb-3 flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-brand-700">
                <x-icon name="help" class="h-4 w-4" />
            </span>
            <p class="text-xs font-bold text-neutral-900">Need help?</p>
            <p class="mt-1 max-w-[170px] text-[11px] leading-relaxed text-neutral-600">Contact HR if you have questions about your leave balance.</p>
        </div>
        <p class="px-3 pt-4 text-[10px] text-neutral-500">Internal portal · MVP</p>
    </div>
</aside>
