@props([
    'href',
    'active' => false,
    'icon' => null,
])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    @class([
        'flex flex-none items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold transition lg:w-full',
        'bg-brand-100 text-brand-700' => $active,
        'text-neutral-600 hover:bg-brand-100 hover:text-brand-700' => ! $active,
    ])
>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 flex-none" />
    @endif

    <span class="whitespace-nowrap">{{ $slot }}</span>
</a>
