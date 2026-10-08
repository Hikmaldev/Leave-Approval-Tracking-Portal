@props([
    'for' => null,
    'required' => false,
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'block text-xs font-bold text-neutral-900']) }}
>
    {{ $slot }}@if ($required)<span class="text-red-700" aria-hidden="true"> *</span><span class="sr-only">(required)</span>@endif
</label>
