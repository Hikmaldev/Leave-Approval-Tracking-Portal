@props([
    'invalid' => false,
])

<textarea {{ $attributes->merge(['class' => 'min-h-28 w-full rounded-md border bg-white px-3 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-500 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/15 '.($invalid ? 'border-red-400' : 'border-neutral-300')]) }}>{{ $slot }}</textarea>
