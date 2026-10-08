<x-layouts.app title="Employee Dashboard" active="dashboard">
    <x-slot:breadcrumbs>
        <span>Workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Dashboard</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Employee dashboard"
        title="Welcome back"
        description="Keep track of your leave requests and available balance in one place."
    >
        <x-slot:actions>
            <x-ui.button :href="route('requests.create')" class="max-sm:w-full">
                <x-icon name="plus" class="h-[17px] w-[17px]" />
                New Request
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <section class="mt-2" aria-labelledby="balance-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.1em] text-neutral-500">At a glance</p>
                <h2 id="balance-heading" class="text-lg font-bold tracking-tight text-neutral-900">Current balance</h2>
            </div>
            <p class="text-xs text-neutral-500">Per leave type · {{ $year }}</p>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @forelse ($balances as $balance)
                <x-balance-card :balance="$balance" />
            @empty
                <div class="flex min-h-[214px] flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 bg-white p-7 text-center sm:col-span-2">
                    <span class="mb-3.5 flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600">
                        <x-icon name="wallet" class="h-5 w-5" />
                    </span>
                    <p class="text-[13px] font-bold text-neutral-900">No balance records yet</p>
                    <p class="mt-1.5 max-w-[250px] text-xs leading-relaxed text-neutral-600">
                        Your configured leave balances will appear here once they are provided by HR.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mt-11" aria-labelledby="requests-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.1em] text-neutral-500">Stay informed</p>
                <h2 id="requests-heading" class="text-lg font-bold tracking-tight text-neutral-900">Recent requests</h2>
            </div>
            <a href="{{ route('requests.index') }}" class="text-xs font-bold text-brand-700 hover:underline">View all requests →</a>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-neutral-300 bg-white shadow-sm">
            @forelse ($recentRequests as $leaveRequest)
                <x-request-card :leave-request="$leaveRequest" />
            @empty
                <x-empty-state
                    title="No requests to show"
                    description="Your submitted leave and permission requests will appear here with their current status."
                >
                    <x-slot:actions>
                        <x-ui.button :href="route('requests.create')" variant="secondary">
                            <x-icon name="plus" class="h-[17px] w-[17px]" />
                            New Request
                        </x-ui.button>
                    </x-slot:actions>
                </x-empty-state>
            @endforelse
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-[#dbeae2] bg-[#f8faf9] px-5 py-4" aria-labelledby="approval-heading">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-3.5">
            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-100 text-brand-700">
                <x-icon name="info" class="h-[18px] w-[18px]" />
            </span>
            <div class="lg:max-w-[395px]">
                <h2 id="approval-heading" class="text-[13px] font-bold text-neutral-900">How approval works</h2>
                <p class="mt-1 text-[11px] leading-relaxed text-neutral-600">Every request follows two steps: your direct supervisor reviews it first, then HR gives the final decision.</p>
            </div>

            <div class="flex items-start overflow-x-auto lg:ml-auto lg:pl-5" role="list" aria-label="Approval chain: Submitted, Supervisor, HR, Final">
                @php
                    $miniSteps = ['Submitted', 'Supervisor', 'HR', 'Final'];
                @endphp

                @foreach ($miniSteps as $index => $step)
                    <div class="flex min-w-14 flex-col items-center gap-1.5 text-center" role="listitem">
                        <span @class([
                            'flex h-6 w-6 items-center justify-center rounded-full border text-[10px] font-bold',
                            'border-brand-700 bg-brand-700 text-white' => $loop->first,
                            'border-neutral-300 bg-white text-neutral-500' => ! $loop->first,
                        ])>{{ $index + 1 }}</span>
                        <span @class([
                            'text-[10px] whitespace-nowrap',
                            'font-semibold text-brand-700' => $loop->first,
                            'text-neutral-500' => ! $loop->first,
                        ])>{{ $step }}</span>
                    </div>

                    @unless ($loop->last)
                        <span class="mx-0.5 mt-3 h-px w-7 flex-none bg-neutral-300"></span>
                    @endunless
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>
