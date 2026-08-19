<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <main class="mx-auto flex min-h-screen max-w-3xl items-center px-6 py-12">
            <section class="w-full rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl shadow-black/20 sm:p-10">
                <livewire:pdf-compressor />
            </section>
        </main>
        @livewireScripts
    </body>
</html>
