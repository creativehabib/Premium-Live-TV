<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>LiveTVPro - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgPKsKTDHK8XChTjvQ68VIMFGTn_CtyGoroGeYWy8syHT22cMCtG6FckHtpsnjNlDU-e6p_KVM6ZjCRMXHjbQNh5hynFJfc5RPi5E63pvVuSFboVLBg1p2BNR6d1csVTMqyHgxA9balq6AM/s60/creativehabib_logo_2.png" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <livewire:tv-player />
    </body>
</html>
