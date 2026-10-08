@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name', 'Leave Portal') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-sm text-neutral-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[minmax(360px,0.85fr)_minmax(480px,1.15fr)]">
        <aside class="flex flex-col justify-between bg-brand-700 px-6 py-8 text-white sm:px-10 lg:px-16 lg:py-12">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-white text-brand-700">
                    <x-icon name="document" class="h-5 w-5" />
                </span>
                <span>
                    <span class="block text-[15px] font-bold leading-tight tracking-tight">Leave Portal</span>
                    <span class="block text-[11px] text-[#c7ded4]">Approval tracking</span>
                </span>
            </div>

            <div class="max-w-[390px] py-12 lg:py-14">
                <p class="mb-3 text-[11px] font-bold uppercase tracking-[0.1em] text-[#c7ded4]">Employee leave portal</p>
                <h1 class="text-3xl font-bold leading-tight tracking-tight lg:text-4xl">A clearer way to manage time away.</h1>
                <p class="mt-4 text-[15px] leading-relaxed text-[#c7ded4]">Submit requests, follow each approval step, and keep your leave balance visible.</p>
            </div>

            <p class="hidden text-[11px] text-[#a8c5b8] lg:block">Internal business tool · Secure access</p>
        </aside>

        <main class="flex items-center justify-center bg-neutral-100 px-5 py-10 sm:px-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
