@props([
    'name',
])

<svg
    {{ $attributes->except('class')->merge([
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.7',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ]) }}
    class="{{ $attributes->get('class', 'h-5 w-5') }}"
>
    @switch($name)
        @case('dashboard')
            <path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"/>
        @break

        @case('document')
            <path d="M7 3.75h10A2.25 2.25 0 0 1 19.25 6v12A2.25 2.25 0 0 1 17 20.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75Zm0 1.5A.75.75 0 0 0 6.25 6v12c0 .414.336.75.75.75h10a.75.75 0 0 0 .75-.75V6a.75.75 0 0 0-.75-.75H7ZM8 8h8v1.5H8V8Zm0 3.5h8V13H8v-1.5Zm0 3.5h5v1.5H8V15Z"/>
        @break

        @case('wallet')
            <path d="M4.75 6.5A2.75 2.75 0 0 1 7.5 3.75h9A2.75 2.75 0 0 1 19.25 6.5v11a2.75 2.75 0 0 1-2.75 2.75h-9a2.75 2.75 0 0 1-2.75-2.75v-11Zm2.75-1.25c-.69 0-1.25.56-1.25 1.25v11c0 .69.56 1.25 1.25 1.25h9c.69 0 1.25-.56 1.25-1.25v-11c0-.69-.56-1.25-1.25-1.25h-9ZM8 9h8v1.5H8V9Zm0 3h6v1.5H8V12Z"/>
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14"/>
        @break

        @case('check')
            <circle cx="12" cy="12" r="8.25"/>
            <path d="m8.75 12.25 2.25 2.25 4.25-4.5"/>
        @break

        @case('tag')
            <path d="M4.75 5.5h5.25l8.25 8.25-5.25 5.25-8.25-8.25V5.5Z"/>
            <path d="M8.1 8.1h.01"/>
        @break

        @case('search')
            <circle cx="11" cy="11" r="6.5"/>
            <path d="m20 20-3.75-3.75"/>
        @break

        @case('upload')
            <path d="M12 15.5V5.25m0 0L8.25 9M12 5.25 15.75 9"/>
            <path d="M5.5 14.75v2.75c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-2.75"/>
        @break

        @case('info')
            <circle cx="12" cy="12" r="8.25"/>
            <path d="M12 11v5"/>
            <path d="M12 8h.01"/>
        @break

        @case('inbox')
            <path d="M5.25 20.25h13.5c.83 0 1.5-.67 1.5-1.5V5.25c0-.83-.67-1.5-1.5-1.5H5.25c-.83 0-1.5.67-1.5 1.5v13.5c0 .83.67 1.5 1.5 1.5Z"/>
            <path d="M3.75 13.75h4.5l1.5 2.25h4.5l1.5-2.25h4.5"/>
        @break

        @case('help')
            <circle cx="12" cy="12" r="8.25"/>
            <path d="M9.75 9.75a2.25 2.25 0 1 1 3.4 1.94c-.66.4-1.15.97-1.15 1.81"/>
            <path d="M12 16.5h.01"/>
        @break

        @case('logout')
            <path d="M15.25 8.5V6.25c0-.69-.56-1.25-1.25-1.25H6.25C5.56 5 5 5.56 5 6.25v11.5c0 .69.56 1.25 1.25 1.25H14c.69 0 1.25-.56 1.25-1.25V15.5"/>
            <path d="M9.5 12h10m0 0-2.75-2.75M19.5 12l-2.75 2.75"/>
        @break

        @case('arrow-left')
            <path d="M10.5 19.5 3 12l7.5-7.5M3 12h18"/>
        @break

        @case('users')
            <path d="M15 19.5v-1.75a3.25 3.25 0 0 0-3.25-3.25h-4.5A3.25 3.25 0 0 0 4 17.75v1.75"/>
            <circle cx="9.5" cy="8" r="3.25"/>
            <path d="M20 19.5v-1.75a3.25 3.25 0 0 0-2.44-3.15M14.75 5.06a3.25 3.25 0 0 1 0 6.3"/>
        @break

        @case('paperclip')
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        @break
    @endswitch
</svg>
