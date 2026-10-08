@props([
    'balance',
])

@php
    $quota = (int) $balance->quota;
    $remaining = $balance->remainingDays();
    $percent = $quota > 0 ? max(0, min(100, (int) round($remaining / $quota * 100))) : 0;
    $barColor = $percent > 25 ? 'bg-brand-600' : ($percent >= 10 ? 'bg-amber-500' : 'bg-red-500');
@endphp

<article {{ $attributes->merge(['class' => 'rounded-lg border border-neutral-300 bg-white p-5 shadow-sm']) }}>
    <div class="mb-4 flex items-center justify-between gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
            <x-icon name="wallet" class="h-5 w-5" />
        </span>
        <span class="rounded bg-neutral-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-neutral-600">{{ $balance->year }}</span>
    </div>

    <h3 class="text-sm font-bold text-neutral-900">{{ $balance->leaveType?->name }}</h3>

    <p class="mt-1 text-[26px] font-bold leading-none tracking-tight tabular-nums text-neutral-900">
        {{ $remaining }}<span class="px-1.5 font-normal text-neutral-300">/</span>{{ $quota }}
    </p>
    <p class="mt-1 text-[11px] text-neutral-600">days remaining</p>

    <div class="mt-4.5 h-[7px] overflow-hidden rounded bg-neutral-200" role="presentation">
        <span class="block h-full rounded {{ $barColor }}" style="width: {{ $percent }}%"></span>
    </div>

    <p class="mt-2 text-[11px] text-neutral-500">{{ $balance->used }} of {{ $quota }} day(s) used this year</p>
</article>
