@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex min-h-[250px] flex-col items-center justify-center px-5 py-8 text-center']) }}>
    <span class="mb-3.5 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
        @if (isset($icon))
            {{ $icon }}
        @else
            <x-icon name="inbox" class="h-6 w-6" />
        @endif
    </span>

    <h3 class="text-sm font-bold text-neutral-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 max-w-md text-xs leading-relaxed text-neutral-600">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endif
</div>
