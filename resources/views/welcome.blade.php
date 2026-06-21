<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Primary Meta Tags -->
    <title>Live TV Pro - Watch World Cup Football & Live TV | {{ config('app.name', 'Creativehabib') }}</title>
    <meta name="title" content="Live TV Pro - Watch World Cup Football & Live TV">
    <meta name="description" content="Watch Live TV and World Cup Football in high quality. Enjoy seamless live streaming of your favorite sports, news, and entertainment channels.">
    <meta name="keywords" content="Live TV, World Cup Football, Live Streaming, Sports Live, Football Match Live, Online TV, Creativehabib">
    <meta name="author" content="{{ config('app.name', 'Creativehabib') }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook (সোশ্যাল মিডিয়ায় শেয়ার করার জন্য) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Live TV Pro - Watch World Cup Football & Live TV">
    <meta property="og:description" content="Watch Live TV and World Cup Football in high quality. Enjoy seamless live streaming of your favorite sports, news, and entertainment channels.">
    <meta property="og:image" content="{{ asset('/images/og_image.jpg') }}">

    <!-- Twitter Card -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="Live TV Pro - Watch World Cup Football & Live TV">
    <meta property="twitter:description" content="Watch Live TV and World Cup Football in high quality. Enjoy seamless live streaming of your favorite sports, news, and entertainment channels.">
    <meta property="twitter:image" content="{{ asset('/images/og_image.jpg') }}">

    <!-- Canonical URL (ডুপ্লিকেট কনটেন্ট ইস্যু এড়াতে) -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Icons -->
    <link rel="icon" href="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgPKsKTDHK8XChTjvQ68VIMFGTn_CtyGoroGeYWy8syHT22cMCtG6FckHtpsnjNlDU-e6p_KVM6ZjCRMXHjbQNh5hynFJfc5RPi5E63pvVuSFboVLBg1p2BNR6d1csVTMqyHgxA9balq6AM/s60/creativehabib_logo_2.png" sizes="any">
    <link rel="icon" href="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgPKsKTDHK8XChTjvQ68VIMFGTn_CtyGoroGeYWy8syHT22cMCtG6FckHtpsnjNlDU-e6p_KVM6ZjCRMXHjbQNh5hynFJfc5RPi5E63pvVuSFboVLBg1p2BNR6d1csVTMqyHgxA9balq6AM/s60/creativehabib_logo_2.png" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google AdSense Main Script -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1344457168985868" crossorigin="anonymous"></script>
</head>
<body class="font-sans antialiased">
<livewire:tv-player />
</body>
</html>
