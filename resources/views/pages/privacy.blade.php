
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - Live TV Pro</title>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,#d8dcff_0,#f9f7ff_28%,#f2ecff_48%,#eafbf8_100%)] dark:bg-slate-950 text-slate-800 dark:text-slate-200 p-4 sm:p-8">

<div class="max-w-3xl mx-auto">
    <a href="/" class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline mb-6">
        &larr; Back to Home
    </a>

    <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/50 dark:border-slate-800 p-6 sm:p-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-2">Privacy Policy</h1>
        <p class="text-sm text-slate-500 mb-8">Last Updated: {{ date('F d, Y') }}</p>

        <div class="space-y-6 text-sm sm:text-base leading-relaxed text-slate-600 dark:text-slate-400">
            <p>At <strong>Live TV Pro</strong>, accessible from <a href="{{ url('/') }}" class="text-indigo-500 hover:underline">{{ url('/') }}</a>, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by Live TV Pro and how we use it.</p>

            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6">1. Log Files and Visitor Tracking</h2>
            <p>Live TV Pro follows a standard procedure of using log files. These files log visitors when they visit websites. The information collected includes internet protocol (IP) addresses, browser type, date and time stamp. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends and gathering demographic information (e.g., our Live Visitor Counter).</p>

            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6">2. Cookies and Web Beacons</h2>
            <p>Like any other website, Live TV Pro uses "cookies". These cookies are used to store information including visitors' preferences, such as saved favorite channels (stored locally on your device), and the pages on the website that the visitor accessed or visited.</p>

            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6">3. Google DoubleClick DART Cookie</h2>
            <p>Google is a third-party vendor on our site. It also uses cookies, known as DART cookies, to serve ads to our site visitors based upon their visit to our site and other sites on the internet. However, visitors may choose to decline the use of DART cookies by visiting the Google ad and content network Privacy Policy at: <a href="https://policies.google.com/technologies/ads" target="_blank" class="text-indigo-500 hover:underline">https://policies.google.com/technologies/ads</a></p>

            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6">4. Third-Party Privacy Policies</h2>
            <p>Live TV Pro's Privacy Policy does not apply to other advertisers or external streaming servers. We play streams from external open-source M3U links. Interacting with those streams may subject you to the privacy policies of those respective third-party providers.</p>

            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6">5. Consent</h2>
            <p>By using our website, you hereby consent to our Privacy Policy and agree to its Terms and Conditions.</p>
        </div>
    </div>
</div>

</body>
</html>
