@props([
    'title' => null,
    'active' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name', 'Leave Portal') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 text-sm text-neutral-900 antialiased">
    <x-sidebar :active="$active" />

    <div class="lg:pl-[248px]">
        <header class="flex min-h-[70px] items-center justify-between gap-4 border-b border-neutral-300 bg-white px-5 py-3 sm:px-8 lg:px-12">
            <div class="hidden items-center gap-2.5 text-xs text-neutral-500 sm:flex">
                {{ $breadcrumbs ?? '' }}
            </div>

            <div class="flex w-full items-center justify-end gap-3 sm:w-auto">
                <div class="flex flex-col items-end leading-tight max-sm:hidden">
                    <span class="text-xs font-semibold text-neutral-900">{{ auth()->user()?->name }}</span>
                    <span class="mt-0.5 text-[11px] text-neutral-500">{{ auth()->user()?->role?->label() }}</span>
                </div>

                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr((string) auth()->user()?->name, 0, 1)) }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex h-9 w-9 items-center justify-center rounded-md text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                        title="Log out"
                    >
                        <x-icon name="logout" class="h-[18px] w-[18px]" />
                        <span class="sr-only">Log out</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1160px] px-5 py-8 sm:px-8 lg:px-12 lg:py-11">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-2.5 rounded-md border border-[#dbeae2] bg-[#f8faf9] px-3.5 py-3 text-xs leading-relaxed text-[#1f4a3a]" role="status">
                    <x-icon name="check" class="mt-px h-[17px] w-[17px] flex-none" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->has('workflow'))
                <div class="mb-6 flex items-start gap-2.5 rounded-md border border-red-300 bg-red-50 px-3.5 py-3 text-xs leading-relaxed text-red-800" role="alert">
                    <x-icon name="info" class="mt-px h-[17px] w-[17px] flex-none" />
                    <span>{{ $errors->first('workflow') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
