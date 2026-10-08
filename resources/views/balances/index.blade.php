<x-layouts.app title="Leave Balance" active="balances.index">
    <x-slot:breadcrumbs>
        <span>Workspace</span>
        <span class="text-neutral-300">/</span>
        <strong class="font-semibold text-neutral-900">Leave Balance</strong>
    </x-slot:breadcrumbs>

    <x-page-header
        eyebrow="Employee workspace"
        title="Leave balance"
        description="See your available balance for each configured leave type before submitting a request."
    />

    <div class="grid gap-4 sm:grid-cols-2">
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

    <section class="mt-6 rounded-lg border border-neutral-300 bg-white p-5 shadow-sm" aria-labelledby="balance-rules-heading">
        <h2 id="balance-rules-heading" class="text-base font-bold text-neutral-900">How balance works</h2>
        <p class="mt-1 text-xs text-neutral-600">The balance is designed to make the impact of a request clear before you submit it.</p>

        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Before approval</dt>
                <dd class="mt-1 text-[13px] text-neutral-900">Submitting a request does not deduct balance.</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">After final approval</dt>
                <dd class="mt-1 text-[13px] text-neutral-900">HR approval deducts the requested days.</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Rejection</dt>
                <dd class="mt-1 text-[13px] text-neutral-900">A rejected request does not change your balance.</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-[0.08em] text-neutral-500">Cancellation</dt>
                <dd class="mt-1 text-[13px] text-neutral-900">A fully approved request restores its deducted days when cancelled.</dd>
            </div>
        </dl>
    </section>
</x-layouts.app>
