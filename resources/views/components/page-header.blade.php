@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="max-w-3xl">
        @if ($eyebrow)
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-brand-700">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-[26px] font-bold leading-tight tracking-tight text-neutral-900 sm:text-3xl">{{ $title }}</h1>

        @if ($description)
            <p class="mt-2 text-sm text-neutral-600">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-none flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</header>
