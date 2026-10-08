@props([
    'messages' => null,
])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-[11px] text-red-700']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
