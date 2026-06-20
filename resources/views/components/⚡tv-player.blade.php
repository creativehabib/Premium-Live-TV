<?php

use Illuminate\Support\Facades\Http;
use Livewire\Component;

new class extends Component {
    public string $singleStreamUrl = 'https://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8';

    public string $playlist = '';

    public string $search = '';

    public string $activeGroupKey = 'sports';

    public int $selectedChannelId = 1;

    public ?string $loadMessage = null;

    /**
     * @var list<array{key:string,name:string,badge:string,url:string}>
     */
    public array $groups = [
        ['key' => 'sports', 'name' => 'Sports ⚽', 'badge' => 'SPORTS', 'url' => 'https://raw.githubusercontent.com/Monjil404/TVspo/refs/heads/main/tvs'],
        ['key' => 'techeasylife', 'name' => 'TechEasyLife 👑', 'badge' => 'BD', 'url' => 'https://raw.githubusercontent.com/Monjil404/livetv/refs/heads/main/pro'],
        ['key' => 'mrgify-bdix', 'name' => 'Mrgify BDIX ⭐', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/abusaeeidx/Mrgify-BDIX-IPTV/main/playlist.m3u'],
        ['key' => 'mrgify-clean', 'name' => 'Mrgify Clean', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/ashik4u/mrgify-clean/main/playlist.m3u'],
        ['key' => 'imshakil', 'name' => 'imShakil', 'badge' => 'BD+INDIA', 'url' => 'https://raw.githubusercontent.com/imShakil/tvlink/refs/heads/main/iptv.m3u8'],
        ['key' => 'xniptv', 'name' => 'Xniptv (140)', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/tvbd/m3uplayer/refs/heads/main/m3u/xniptv.m3u'],
        ['key' => 'time2shine', 'name' => 'time2shine', 'badge' => 'BIG', 'url' => 'https://raw.githubusercontent.com/time2shine/IPTV/master/combined.m3u'],
        ['key' => 'shamimhossain', 'name' => 'ShamimHossain', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/ShamimHossainOfficial/IPTV/master/BDIX-IPTV.m3u8'],
        ['key' => 'shadmanislam', 'name' => 'Shadmanislam', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/Shadmanislam/bdiptv/master/BD%20IPTV.m3u'],
        ['key' => 'drsujonpaul', 'name' => 'DrSujonPaul', 'badge' => 'BDIX', 'url' => 'https://raw.githubusercontent.com/DrSujonPaul/Sujon/6dc6a1d4eaa20a9239ae27d8e0f00182b60eeb47/iptv'],
        ['key' => 'akash-live', 'name' => 'Akash Live', 'badge' => 'BD', 'url' => 'https://raw.githubusercontent.com/srhady/Hady/refs/heads/main/akash_live.m3u'],
        ['key' => 'bugsfree-bd', 'name' => 'Bugsfree BD', 'badge' => 'BIG', 'url' => 'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Bangladesh/LiveTV.m3u'],
        ['key' => 'bugsfree-india', 'name' => 'Bugsfree India', 'badge' => 'INDIA', 'url' => 'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/India/LiveTV.m3u'],
        ['key' => 'lupael', 'name' => 'lupael', 'badge' => 'MIXED', 'url' => 'https://lupael.github.io/IPTV/running.m3u'],
        ['key' => 'axsport', 'name' => 'Axsport', 'badge' => 'SPORTS', 'url' => 'https://raw.githubusercontent.com/srhady/axsports/refs/heads/main/playlist.m3u'],
    ];

    /**
     * @var list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int,proxy_needed:bool,is_http:bool}>
     */
    public array $channels = [];

    public function mount(): void
    {
        $this->loadGroup($this->activeGroupKey);
    }

    /**
     * @return list<array>
     */
    public function filteredChannels(): array
    {
        return array_values(array_filter($this->channels, function (array $channel): bool {
            return $this->search === '' || str_contains(strtolower($channel['name'].' '.$channel['category'].' '.$channel['url']), strtolower($this->search));
        }));
    }

    public function loadGroup(string $groupKey): void
    {
        $group = collect($this->groups)->firstWhere('key', $groupKey);

        if ($group === null) {
            return;
        }

        $this->activeGroupKey = $groupKey;
        $this->search = '';
        $this->loadMessage = 'Loading '.$group['name'].' channels...';

        $response = Http::timeout(12)->get($group['url']);

        if (! $response->successful()) {
            $this->channels = [];
            $this->loadMessage = 'Unable to load channels from '.$group['name'].'.';

            return;
        }

        $this->playlist = $response->body();
        $this->channels = $this->parsePlaylist($this->playlist, $group['name']);
        $this->loadMessage = count($this->channels).' channels loaded from '.$group['name'].'.';

        if ($this->channels !== []) {
            $this->selectedChannelId = $this->channels[0]['id'];
            $this->singleStreamUrl = $this->channels[0]['url'];
            $this->dispatch('stream-selected', url: $this->channels[0]['url'], name: $this->channels[0]['name']);
        }
    }

    public function playChannel(int $channelId): void
    {
        $channel = collect($this->channels)->firstWhere('id', $channelId);

        if ($channel === null) {
            return;
        }

        $this->selectedChannelId = $channelId;
        $this->singleStreamUrl = $channel['url'];
        $this->dispatch('stream-selected', url: $channel['url'], name: $channel['name']);
    }

    public function playSingleStream(): void
    {
        $this->dispatch('stream-selected', url: $this->singleStreamUrl, name: 'Custom Stream');
    }

    public function clearInputs(): void
    {
        $this->singleStreamUrl = '';
        $this->playlist = '';
    }

    // --- LOGIC: Verify, Delete, and Clean Channels ---

    public function verifyChannel(int $id): void
    {
        $index = array_search($id, array_column($this->channels, 'id'));
        if ($index === false) return;

        $url = $this->channels[$index]['url'];

        if (str_starts_with($url, 'http://')) {
            $this->channels[$index]['status'] = 'BLOCKED';
            return;
        }

        try {
            $response = Http::timeout(5)->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get($url);

            if ($response->successful()) {
                $this->channels[$index]['status'] = 'LIVE';
            } elseif (in_array($response->status(), [401, 403])) {
                $this->channels[$index]['status'] = 'BLOCKED';
                $this->channels[$index]['proxy_needed'] = true;
            } else {
                $this->channels[$index]['status'] = 'DEAD';
            }
        } catch (\Exception $e) {
            $this->channels[$index]['status'] = 'DEAD';
        }
    }

    public function deleteChannel(int $id): void
    {
        $this->channels = array_values(array_filter($this->channels, fn($c) => $c['id'] !== $id));
    }

    public function deleteBlockedChannels(): void
    {
        $this->channels = array_values(array_filter($this->channels, fn($c) => $c['status'] !== 'BLOCKED'));
    }

    public function deleteDeadChannels(): void
    {
        $this->channels = array_values(array_filter($this->channels, fn($c) => $c['status'] !== 'DEAD'));
    }

    public function selectedChannel(): array
    {
        return collect($this->channels)->firstWhere('id', $this->selectedChannelId) ?? [
            'id' => 0,
            'name' => 'Select a channel',
            'category' => 'Playlist',
            'url' => $this->singleStreamUrl,
            'logo' => 'TV',
            'status' => 'READY',
            'protocol' => 'HLS',
            'response' => 'Pending',
            'viewers' => 0,
        ];
    }

    private function parsePlaylist(string $playlist, string $fallbackCategory): array
    {
        $channels = [];
        $pendingName = null;
        $pendingCategory = $fallbackCategory;
        $pendingLogo = null;
        $lines = preg_split('/\R/', $playlist) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#EXTINF')) {
                $pendingName = str($line)->afterLast(',')->trim()->toString() ?: 'Untitled Channel';
                preg_match('/group-title="([^"]+)"/i', $line, $matches);
                $pendingCategory = $matches[1] ?? $fallbackCategory;

                preg_match('/tvg-logo="([^"]+)"/i', $line, $logoMatches);
                $pendingLogo = $logoMatches[1] ?? null;

                continue;
            }

            if (str_starts_with($line, '#') || ! preg_match('/^https?:\/\//i', $line)) {
                continue;
            }

            $name = $pendingName ?? basename(parse_url($line, PHP_URL_PATH) ?: 'Live Channel');

            $status = 'LIVE';
            $isHttp = false;
            $proxyNeeded = false;

            if (str_starts_with($line, 'http://')) {
                $status = 'BLOCKED';
                $isHttp = true;
            } elseif (str_contains(strtolower($line), '.ts') || str_contains(strtolower($line), 'dead')) {
                $status = 'DEAD';
            } elseif (str_contains(strtolower($line), 'ncare') || str_contains(strtolower($line), 'proxy')) {
                $status = 'BLOCKED';
                $proxyNeeded = true;
            }

            $channels[] = [
                'id' => count($channels) + 1,
                'name' => $name,
                'category' => $pendingCategory,
                'url' => $line,
                'logo' => $pendingLogo ?: str($name)->substr(0, 2)->upper()->toString(),
                'status' => $status,
                'proxy_needed' => $proxyNeeded,
                'is_http' => $isHttp,
                'protocol' => str_contains(strtolower($line), '.mpd') ? 'DASH' : 'HLS',
                'response' => 'Loaded',
                'viewers' => 0,
            ];
            $pendingName = null;
            $pendingCategory = $fallbackCategory;
            $pendingLogo = null;
        }

        return $channels;
    }
};
?>
<div id="app-wrapper"
     x-data="{
         favorites: JSON.parse(localStorage.getItem('tvPro_favorites') || '[]'),
         showFavoritesOnly: false,
         toggleFav(url) {
             if(this.favorites.includes(url)) {
                 this.favorites = this.favorites.filter(u => u !== url);
                 window.showToast('ফেভারিট থেকে মুছে ফেলা হয়েছে', 'error');
             } else {
                 this.favorites.push(url);
                 window.showToast('ফেভারিটে যুক্ত হয়েছে', 'success');
             }
             localStorage.setItem('tvPro_favorites', JSON.stringify(this.favorites));
         },
         isFav(url) {
             return this.favorites.includes(url);
         }
     }"
     class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,#d8dcff_0,#f9f7ff_28%,#f2ecff_48%,#eafbf8_100%)] text-slate-800 dark:bg-slate-950 dark:text-slate-100 relative transition-colors duration-500">

    <style>
        /* IMPORT PREMIUM FONTS */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap');

        /* TYPOGRAPHY REFINEMENT */
        #app-wrapper { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        /* CUSTOM SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.7); }
        .dark ::-webkit-scrollbar-thumb { background: rgba(71, 85, 105, 0.5); }
        .dark ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.8); }
        * { scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.4) transparent; }

        /* Custom Volume Slider CSS */
        input[type=range].custom-vol { -webkit-appearance: none; background: rgba(255, 255, 255, 0.4); height: 4px; border-radius: 2px; outline: none; }
        input[type=range].custom-vol::-webkit-slider-thumb { -webkit-appearance: none; height: 12px; width: 12px; border-radius: 50%; background: #ffffff; cursor: pointer; box-shadow: 0 0 5px rgba(0,0,0,0.5); transition: transform 0.1s; }
        input[type=range].custom-vol::-webkit-slider-thumb:hover { transform: scale(1.2); }

        /* Micro-interaction: Staggered Fade In Up */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .stagger-fade-in > div { animation: fadeInUp 0.4s ease-out forwards; opacity: 0; }
        .stagger-fade-in > div:nth-child(1) { animation-delay: 0.05s; } .stagger-fade-in > div:nth-child(2) { animation-delay: 0.1s; }
        .stagger-fade-in > div:nth-child(3) { animation-delay: 0.15s; } .stagger-fade-in > div:nth-child(4) { animation-delay: 0.2s; }
        .stagger-fade-in > div:nth-child(n+5) { animation-delay: 0.25s; }

        /* THEATER MODE STYLES */
        body.theater-mode-active #app-wrapper { background: #020617 !important; }
        body.theater-mode-active #content-container { max-width: 1500px !important; }
        body.theater-mode-active .dim-in-theater { opacity: 0.15; transition: opacity 0.4s ease-in-out; filter: grayscale(0.5); }
        body.theater-mode-active .dim-in-theater:hover { opacity: 1; filter: grayscale(0); }
        body.theater-mode-active .player-section-wrapper { box-shadow: 0 0 50px rgba(0,0,0,0.5); border-color: #1e293b; }
        #content-container { transition: max-width 0.5s cubic-bezier(0.4, 0, 0.2, 1); }

        /* MULTI-VIEW / VIDEO GRID STYLES */
        .video-slot { position: relative; width: 100%; height: 100%; background: #000; transition: all 0.3s ease; cursor: pointer; }
        .video-slot video { width: 100%; height: 100%; object-fit: contain; }
        .slot-active { z-index: 10; box-shadow: inset 0 0 0 2px #6366f1; opacity: 1 !important; }
        .slot-inactive { z-index: 0; opacity: 0.6; }
        .slot-inactive:hover { opacity: 0.9; box-shadow: inset 0 0 0 2px rgba(255,255,255,0.3); }

        /* DRAG AND DROP STYLES */
        .drag-over-active { box-shadow: inset 0 0 0 4px #f43f5e !important; filter: brightness(1.2); }

        /* FLOATING MINI-PLAYER STYLES */
        .floating-player {
            position: fixed !important;
            bottom: 25px;
            right: 25px;
            width: 380px !important;
            height: 214px !important;
            z-index: 99999;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: #000;
        }
        .floating-player:hover { transform: scale(1.03) translateY(-5px); box-shadow: 0 30px 60px -10px rgba(0, 0, 0, 0.7), 0 0 0 2px rgba(99, 102, 241, 0.5); }
        .close-floating { display: none; }
        .floating-player .close-floating { display: flex; }
        .floating-player #custom-controls { padding: 8px !important; }

        /* CUSTOM CONTEXT MENU & MODALS */
        .glass-menu { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); }
        #custom-context-menu { transform-origin: top left; transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease; }

        /* FULLSCREEN HOVER SIDEBAR (LEFT SIDE) */
        .fs-sidebar-wrapper { position: absolute; top: 0; left: 0; width: 320px; height: 100%; z-index: 99999; pointer-events: none; display: none; }
        .video-is-fullscreen .fs-sidebar-wrapper { display: block; }
        .fs-sidebar-trigger { position: absolute; top: 0; left: 0; width: 40px; height: 100%; pointer-events: auto; z-index: 100000; }
        .fs-sidebar-panel { position: absolute; top: 0; left: 0; width: 320px; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-right: 1px solid rgba(255, 255, 255, 0.1); transform: translateX(-100%); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: auto; z-index: 100001; display: flex; flex-direction: column; box-shadow: 20px 0 40px rgba(0,0,0,0.5); }
        .fs-sidebar-trigger:hover + .fs-sidebar-panel, .fs-sidebar-panel:hover { transform: translateX(0); }
        .fs-sidebar-panel::-webkit-scrollbar { width: 5px; } .fs-sidebar-panel::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.6); border-radius: 10px; } .fs-sidebar-panel::-webkit-scrollbar-track { background: transparent; }
    </style>

    <div id="shortcuts-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity opacity-0">
        <div class="glass-menu w-full max-w-md rounded-2xl p-6 text-white transform scale-95 transition-transform duration-300">
            <div class="flex items-center justify-between border-b border-slate-700 pb-3">
                <h3 class="text-lg font-bold">Keyboard Shortcuts</h3>
                <button onclick="toggleShortcutsModal()" class="rounded-full p-1 hover:bg-slate-800 transition">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="mt-4 space-y-3">
                <div class="flex justify-between"><span class="text-slate-400">Play / Pause</span> <kbd class="rounded bg-slate-800 px-2 py-1 text-xs font-mono border border-slate-700">Space</kbd></div>
                <div class="flex justify-between"><span class="text-slate-400">Mute / Unmute</span> <kbd class="rounded bg-slate-800 px-2 py-1 text-xs font-mono border border-slate-700">M</kbd></div>
                <div class="flex justify-between"><span class="text-slate-400">Fullscreen</span> <kbd class="rounded bg-slate-800 px-2 py-1 text-xs font-mono border border-slate-700">F</kbd></div>
                <div class="flex justify-between"><span class="text-slate-400">Theater Mode</span> <kbd class="rounded bg-slate-800 px-2 py-1 text-xs font-mono border border-slate-700">T</kbd></div>
                <div class="flex justify-between"><span class="text-slate-400">Close Menus</span> <kbd class="rounded bg-slate-800 px-2 py-1 text-xs font-mono border border-slate-700">Esc</kbd></div>
            </div>
        </div>
    </div>

    <div id="content-container" class="mx-auto flex w-full max-w-4xl flex-col gap-5 px-4 py-6 sm:py-8">

        <header class="text-center dim-in-theater">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-2 text-sm font-bold text-violet-700 shadow-lg shadow-violet-200/60 ring-1 ring-violet-100 hover:shadow-violet-300 transition duration-300">
                <span class="grid size-7 place-items-center rounded-xl bg-gradient-to-br from-violet-600 to-sky-500 text-white">▶</span>
                LiveTVPro
            </div>
            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400">HLS / DASH / M3U8 Live Streaming Portal</p>
            <div class="mt-3 flex justify-center gap-2 text-[11px] font-bold">
                <span class="rounded-full bg-white/80 px-3 py-1 text-violet-600 shadow-sm transition hover:bg-white hover:-translate-y-0.5 cursor-pointer tracking-wide">● Groups: {{ count($groups) }}</span>
                <span class="rounded-full bg-sky-500 px-3 py-1 text-white shadow-sm transition hover:bg-sky-400 hover:-translate-y-0.5 active:scale-95 cursor-pointer tracking-wide">Join Telegram</span>
            </div>
        </header>

        <section class="dim-in-theater rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700 transition-all duration-300 hover:shadow-violet-200">
            <div class="flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-[0.1em] text-violet-600">
                <span>● Single stream URL</span>
                <span class="text-slate-400">HLS / DASH / HTML5</span>
            </div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input wire:model="singleStreamUrl" class="min-h-11 flex-1 rounded-xl border border-violet-100 bg-violet-50/40 px-3 text-xs outline-none ring-violet-300 transition-all duration-300 focus:ring-2 focus:-translate-y-0.5 focus:shadow-md dark:border-slate-700 dark:bg-slate-800" placeholder="https://example.com/stream.m3u8">
                <button wire:click="playSingleStream" class="rounded-xl bg-violet-600 px-5 py-3 cursor-pointer text-xs font-bold text-white shadow-lg shadow-violet-300 transition-all duration-300 hover:bg-violet-700 hover:-translate-y-0.5 active:scale-95 hover:shadow-violet-400/50">▶ Play</button>
                <button type="button" class="rounded-xl border border-violet-100 bg-white px-4 py-3 cursor-pointer text-xs font-bold text-violet-600 shadow-sm dark:border-slate-700 dark:bg-slate-800 transition-all duration-200 hover:bg-violet-50 active:scale-95">ⓘ Check</button>
            </div>
            <label class="mt-4 block text-[11px] font-bold uppercase tracking-[0.1em] text-violet-600">Playlist / M3U / HTML embed</label>
            <textarea wire:model="playlist" rows="4" class="mt-2 w-full rounded-xl border border-violet-100 bg-violet-50/40 p-3 text-xs outline-none ring-violet-300 transition-all duration-300 focus:ring-2 focus:-translate-y-0.5 focus:shadow-md dark:border-slate-700 dark:bg-slate-800"></textarea>
            <div class="mt-3 flex gap-2">
                <button wire:click="loadGroup('{{ $activeGroupKey }}')" class="rounded-xl bg-violet-600 px-4 py-2 cursor-pointer text-xs font-bold text-white transition-all duration-200 hover:bg-violet-700 hover:-translate-y-0.5 hover:shadow-md active:scale-95">Reload Group</button>
                <button wire:click="clearInputs" class="rounded-xl bg-slate-100 px-4 py-2 cursor-pointer text-xs font-bold text-slate-500 dark:bg-slate-800 transition-all duration-200 hover:bg-slate-200 active:scale-95">Clear</button>
            </div>
        </section>

        <section class="dim-in-theater rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700 transition-all duration-300 hover:shadow-violet-200">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-[11px] font-bold uppercase tracking-[0.1em] text-violet-600">
                <span>📡 পাবলিক সোর্স — ক্লিক করে লোড + চেক</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-500 dark:bg-slate-800 transition hover:bg-slate-200 tracking-wide">{{ count($this->filteredChannels()) }} visible</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($groups as $group)
                    <button wire:key="group-{{ $group['key'] }}" wire:click="loadGroup('{{ $group['key'] }}')" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-all duration-300 active:scale-95 hover:-translate-y-0.5 hover:shadow-md {{ $activeGroupKey === $group['key'] ? 'border-violet-500 bg-violet-600 text-white shadow-lg shadow-violet-200/60' : 'border-violet-100 bg-white text-slate-600 hover:border-violet-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                        <span class="tracking-wide">{{ $group['name'] }}</span>
                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider transition-colors {{ $activeGroupKey === $group['key'] ? 'bg-white/20 text-white' : 'bg-violet-50 text-violet-600' }}">{{ $group['badge'] }}</span>
                    </button>
                @endforeach
            </div>
            @if ($loadMessage)
                <p class="mt-3 text-xs font-semibold text-slate-500 animate-pulse tracking-wide">⚠️ {{ $loadMessage }}</p>
            @endif
            <p class="mt-3 text-[11px] font-medium text-slate-400">কিছু list (time2shine/Bugsfree) থেকে হাজার+ চ্যানেল আসতে পারে। Axsport referer-locked হলে browser-এ ব্লক দেখাবে, VLC-তে চলতে পারে।</p>
        </section>

        <section class="player-section-wrapper overflow-hidden rounded-2xl bg-white shadow-xl shadow-violet-200/50 ring-1 ring-white/80 dark:bg-slate-900 dark:ring-slate-700 transition-all duration-500 hover:shadow-violet-200/60">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-50 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div id="active-channel-logo" class="grid size-11 shrink-0 place-items-center rounded-[10px] border border-slate-200 bg-white overflow-hidden text-xs font-black text-indigo-600 shadow-sm dark:border-slate-700 dark:bg-slate-800 transition-transform duration-300 hover:scale-105">
                        @if(str_starts_with($this->selectedChannel()['logo'], 'http'))
                            <img src="{{ $this->selectedChannel()['logo'] }}" alt="Logo" class="size-full object-contain p-0.5">
                        @else
                            {{ $this->selectedChannel()['logo'] }}
                        @endif
                    </div>
                    <div class="flex flex-col justify-center">
                        <h2 id="active-channel-title" class="text-[15px] font-extrabold text-slate-800 dark:text-slate-100 leading-tight transition-colors hover:text-indigo-600 tracking-wide">
                            {{ $this->selectedChannel()['name'] }} <span class="font-semibold text-slate-500">· {{ $this->selectedChannel()['category'] }}</span>
                        </h2>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                                <svg class="size-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M10 9l5 3-5 3z"></path></svg>
                                LIVE PLAYER
                            </div>
                            <span class="flex items-center gap-1.5 rounded-full bg-emerald-100/60 px-2 py-0.5 text-[10px] font-bold text-emerald-600 ring-1 ring-emerald-200/60 dark:bg-emerald-900/30 dark:ring-emerald-800 shadow-sm tracking-wider">
                                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                LIVE
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button id="top-fullscreen-btn" class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-50 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 tracking-wide">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        Full
                    </button>
                    <button id="top-stop-btn" class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 hover:-translate-y-0.5 active:scale-95 dark:border-rose-900/40 dark:bg-slate-800 dark:hover:bg-rose-900/60 tracking-wide">
                        <span class="size-2 rounded-sm bg-rose-500"></span>
                        Stop
                    </button>
                </div>
            </div>

            <div id="video-placeholder" class="w-full aspect-video relative bg-black/5 dark:bg-black/20 rounded-b-2xl sm:rounded-none">
                <div wire:ignore id="video-container" class="relative w-full h-full bg-black overflow-hidden transition-all group z-40">

                    <button onclick="document.getElementById('video-container').classList.remove('floating-player'); window.scrollTo({top: document.getElementById('video-placeholder').offsetTop - 100, behavior: 'smooth'})" class="close-floating absolute top-2 right-2 size-8 bg-rose-600 text-white rounded-full items-center justify-center shadow-lg z-[10000] hover:bg-rose-700 transition-transform hover:scale-110 active:scale-95">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <div id="custom-context-menu" class="absolute hidden glass-menu rounded-xl py-2 w-48 z-[100] text-sm text-slate-200">
                        <button onclick="copyToClipboard('{{ $this->selectedChannel()['url'] }}'); hideContextMenu();" class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center gap-2">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Copy Video URL
                        </button>
                        <button onclick="toggleLoop(); hideContextMenu();" class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center gap-2">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Loop Video <span id="loop-status" class="ml-auto text-xs text-slate-400">Off</span>
                        </button>
                        <div class="h-px bg-slate-700 my-1"></div>
                        <button onclick="toggleStats(); hideContextMenu();" class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center gap-2">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Stats for nerds
                        </button>
                    </div>

                    <div id="stats-box" class="absolute top-4 left-4 glass-menu rounded-lg p-3 text-[11px] font-mono text-slate-200 hidden z-50">
                        <div class="flex justify-between items-center mb-2 border-b border-slate-700 pb-1">
                            <span class="font-bold text-white">Video Stats</span>
                            <button onclick="toggleStats()" class="hover:text-white">✕</button>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                            <span class="text-slate-400">Resolution:</span> <span id="stat-res">-</span>
                            <span class="text-slate-400">Bandwidth:</span> <span id="stat-bw">-</span>
                            <span class="text-slate-400">Dropped Frames:</span> <span id="stat-frames">-</span>
                            <span class="text-slate-400">Protocol:</span> <span id="stat-proto">{{ $this->selectedChannel()['protocol'] }}</span>
                        </div>
                    </div>

                    <div id="video-grid" class="absolute inset-0 grid grid-cols-1 grid-rows-1 gap-[2px] bg-slate-900 transition-all duration-300">
                        <div id="slot-1" class="video-slot slot-active" onclick="setActiveSlot(1)"
                             ondragover="event.preventDefault(); this.classList.add('drag-over-active')" ondragleave="this.classList.remove('drag-over-active')" ondrop="event.preventDefault(); this.classList.remove('drag-over-active'); handleDrop(event, 1)">
                            <video id="player-1" autoplay muted playsinline x-webkit-airplay="allow"></video>
                            <div id="spinner-1" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 transition-opacity duration-300 z-10">
                                <div class="size-10 rounded-full border-4 border-white/20 border-t-indigo-500 animate-spin shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                            </div>
                            <div id="status-1" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-black/70 px-4 py-2 text-xs font-bold text-white shadow-lg backdrop-blur tracking-wide z-20 opacity-0 transition-opacity duration-300">Ready</div>
                            <div class="absolute top-2 left-2 bg-black/60 px-2 py-1 text-[10px] font-bold tracking-wider text-white rounded backdrop-blur">Screen 1</div>
                        </div>

                        <div id="slot-2" class="video-slot slot-inactive hidden" onclick="setActiveSlot(2)"
                             ondragover="event.preventDefault(); this.classList.add('drag-over-active')" ondragleave="this.classList.remove('drag-over-active')" ondrop="event.preventDefault(); this.classList.remove('drag-over-active'); handleDrop(event, 2)">
                            <video id="player-2" autoplay muted playsinline x-webkit-airplay="allow"></video>
                            <div id="spinner-2" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 transition-opacity duration-300 z-10">
                                <div class="size-10 rounded-full border-4 border-white/20 border-t-indigo-500 animate-spin shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                            </div>
                            <div id="status-2" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-black/70 px-4 py-2 text-xs font-bold text-white shadow-lg backdrop-blur tracking-wide z-20 opacity-0 transition-opacity duration-300">Ready</div>
                            <div class="absolute top-2 left-2 bg-black/60 px-2 py-1 text-[10px] font-bold tracking-wider text-white rounded backdrop-blur">Screen 2</div>
                        </div>

                        <div id="slot-3" class="video-slot slot-inactive hidden" onclick="setActiveSlot(3)"
                             ondragover="event.preventDefault(); this.classList.add('drag-over-active')" ondragleave="this.classList.remove('drag-over-active')" ondrop="event.preventDefault(); this.classList.remove('drag-over-active'); handleDrop(event, 3)">
                            <video id="player-3" autoplay muted playsinline x-webkit-airplay="allow"></video>
                            <div id="spinner-3" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 transition-opacity duration-300 z-10">
                                <div class="size-10 rounded-full border-4 border-white/20 border-t-indigo-500 animate-spin shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                            </div>
                            <div id="status-3" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-black/70 px-4 py-2 text-xs font-bold text-white shadow-lg backdrop-blur tracking-wide z-20 opacity-0 transition-opacity duration-300">Ready</div>
                            <div class="absolute top-2 left-2 bg-black/60 px-2 py-1 text-[10px] font-bold tracking-wider text-white rounded backdrop-blur">Screen 3</div>
                        </div>

                        <div id="slot-4" class="video-slot slot-inactive hidden" onclick="setActiveSlot(4)"
                             ondragover="event.preventDefault(); this.classList.add('drag-over-active')" ondragleave="this.classList.remove('drag-over-active')" ondrop="event.preventDefault(); this.classList.remove('drag-over-active'); handleDrop(event, 4)">
                            <video id="player-4" autoplay muted playsinline x-webkit-airplay="allow"></video>
                            <div id="spinner-4" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 transition-opacity duration-300 z-10">
                                <div class="size-10 rounded-full border-4 border-white/20 border-t-indigo-500 animate-spin shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                            </div>
                            <div id="status-4" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-black/70 px-4 py-2 text-xs font-bold text-white shadow-lg backdrop-blur tracking-wide z-20 opacity-0 transition-opacity duration-300">Ready</div>
                            <div class="absolute top-2 left-2 bg-black/60 px-2 py-1 text-[10px] font-bold tracking-wider text-white rounded backdrop-blur">Screen 4</div>
                        </div>
                    </div>

                    <div id="custom-controls" class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/80 via-black/30 to-transparent p-4 px-5 opacity-100 transition-opacity duration-500 z-30">
                        <div class="flex items-center gap-4">
                            <button id="play-pause-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                            </button>
                            <div class="group/vol flex items-center gap-2">
                                <button id="mute-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90">
                                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
                                </button>
                                <input type="range" id="volume-slider" class="custom-vol w-0 cursor-pointer opacity-0 transition-all duration-300 group-hover/vol:w-20 group-hover/vol:opacity-100" min="0" max="1" step="0.05" value="0">
                            </div>
                            <div class="flex items-center gap-2 font-bold text-white ml-2">
                                <span class="size-2.5 animate-pulse rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,1)]"></span>
                                <span class="text-sm font-extrabold tracking-wider">LIVE</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 relative">
                            <div class="relative group/settings">
                                <button class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Settings">
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </button>
                                <div class="absolute bottom-12 right-0 hidden group-hover/settings:block w-36 glass-menu rounded-xl py-2 shadow-xl border border-white/10 z-50">
                                    <div class="px-4 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quality</div>
                                    <div id="quality-list" class="flex flex-col text-sm text-white">
                                        <button onclick="setQuality(-1)" class="text-left px-4 py-1.5 hover:bg-white/20 transition flex items-center gap-2 text-indigo-300">
                                            <span class="size-1.5 rounded-full bg-indigo-500"></span> Auto
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button id="layout-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Multi-View / Split Screen">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" /></svg>
                            </button>

                            <button id="airplay-btn" class="hidden grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="AirPlay">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4l-4 4m4-4l4 4m-4-4v12"></path></svg>
                            </button>

                            <button id="cast-btn" class="hidden grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Chromecast">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M2 16.1A5 5 0 0 1 5.9 20M2 12.05A9 9 0 0 1 9.95 20M2 8V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6M2 20h.01" /></svg>
                            </button>

                            <button id="theater-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Theater Mode (T)">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"></path></svg>
                            </button>
                            <button id="pip-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Picture-in-Picture">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4.5" width="20" height="15" rx="2"></rect><rect x="12" y="11" width="8" height="6" rx="1" fill="currentColor" stroke="none"></rect></svg>
                            </button>
                            <button id="fullscreen-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 hover:scale-110 active:scale-90" title="Fullscreen (F / Double Click)">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 00-2 2v3M21 8V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3M16 21h3a2 2 0 002-2v-3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-4 divide-x divide-violet-100 border-t border-violet-100 text-center text-xs dark:divide-slate-700 dark:border-slate-700 transition-colors">
                <div class="p-3 hover:bg-slate-50 transition cursor-default"><strong class="block text-violet-600 text-sm">{{ count($channels) }}</strong><span class="text-slate-400 font-medium">Total</span></div>
                <div class="p-3 hover:bg-emerald-50 transition cursor-default"><strong class="block text-emerald-500 text-sm">{{ count($channels) }}</strong><span class="text-slate-400 font-medium">Live</span></div>
                <div class="p-3 hover:bg-orange-50 transition cursor-default"><strong class="block text-orange-500 text-sm">0</strong><span class="text-slate-400 font-medium">Down</span></div>
                <div class="p-3 hover:bg-rose-50 transition cursor-default"><strong class="block text-rose-500 text-sm">0</strong><span class="text-slate-400 font-medium">Errors</span></div>
            </div>
        </section>

        <section class="dim-in-theater rounded-2xl bg-white shadow-xl shadow-indigo-200/50 ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-slate-800 transition-shadow hover:shadow-indigo-200">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-1 bg-slate-50 p-1.5 rounded-xl dark:bg-slate-800/50">
                    <button class="flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 cursor-pointer text-xs font-bold text-indigo-700 shadow-sm ring-1 ring-slate-200/50 dark:bg-slate-700 dark:text-indigo-300 dark:ring-slate-600 transition-all duration-200 active:scale-95 hover:shadow-md hover:-translate-y-0.5 tracking-wide">
                        সব <span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 transition-colors">{{ count($this->filteredChannels()) }}</span>
                    </button>

                    <button @click="showFavoritesOnly = !showFavoritesOnly"
                            :class="showFavoritesOnly ? 'bg-rose-500 text-white shadow-md border-rose-500' : 'bg-white text-rose-500 hover:bg-rose-50 dark:bg-slate-700 dark:text-rose-400 dark:hover:bg-slate-600 dark:border-rose-900/50'"
                            class="flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-1.5 cursor-pointer text-xs font-bold shadow-sm transition-all duration-200 active:scale-95 tracking-wide">
                        <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        ফেভারিট (<span x-text="favorites.length"></span>)
                    </button>

                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all duration-200 active:scale-95 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700 tracking-wide">
                        <span class="size-2 rounded-full bg-emerald-500"></span> চলবে <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ count($this->filteredChannels()) }}</span>
                    </button>
                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all duration-200 active:scale-95 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700 tracking-wide">
                        <span class="size-2 rounded-full bg-orange-500"></span> ব্লকড <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">0</span>
                    </button>
                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all duration-200 active:scale-95 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-700 tracking-wide">
                        <span class="size-2 rounded-full bg-rose-500"></span> ডেড <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">0</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button class="flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-600 shadow-sm transition-all duration-200 hover:bg-indigo-50 active:scale-95 hover:-translate-y-0.5 dark:border-indigo-900/50 dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-900/30 tracking-wide">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg>
                        চলবে কপি
                    </button>
                    <button class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-600 shadow-sm transition-all duration-200 hover:bg-indigo-50 active:scale-95 hover:-translate-y-0.5 dark:border-indigo-900/50 dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-900/30 tracking-wide">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        .m3u
                    </button>
                    <button wire:click="deleteBlockedChannels" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 active:scale-95 hover:-translate-y-0.5 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30 tracking-wide">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        ব্লকড ডিলিট
                    </button>
                    <button wire:click="deleteDeadChannels" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 active:scale-95 hover:-translate-y-0.5 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30 tracking-wide">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        ডেড ডিলিট
                    </button>
                </div>
            </div>

            <div class="px-4 py-3">
                <div class="relative group">
                    <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input wire:model.live="search" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-9 pr-10 text-sm font-medium text-slate-700 outline-none transition-all duration-300 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100 focus:shadow-md focus:-translate-y-0.5 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-indigo-500 dark:focus:bg-slate-900 dark:focus:ring-indigo-900/50" placeholder="নাম, group বা URL দিয়ে সার্চ করুন...">
                    @if($search !== '')
                        <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 grid size-5 -translate-y-1/2 place-items-center rounded-full bg-slate-200 text-slate-500 hover:bg-rose-500 hover:text-white transition-all active:scale-90 dark:bg-slate-700 dark:text-slate-400">
                            <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between px-4 pb-2 pt-1 border-b border-slate-50 dark:border-slate-800">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-500 cursor-pointer hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors">
                    <input type="checkbox" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-600 dark:focus:ring-offset-slate-900 cursor-pointer transition-transform active:scale-90">
                    সব নির্বাচন করুন
                </label>
                <span class="text-xs font-semibold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full dark:bg-slate-800">
                    <span x-show="!showFavoritesOnly">{{ count($this->filteredChannels()) }}টি</span>
                    <span x-show="showFavoritesOnly" x-text="favorites.length + 'টি'" style="display: none;"></span>
                </span>
            </div>

            <div wire:loading wire:target="loadGroup" class="flex w-full flex-col gap-2 p-3">
                @for ($i = 0; $i < 5; $i++)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3 animate-pulse dark:border-slate-800 dark:bg-slate-800/50">
                        <div class="size-4 rounded bg-slate-200 dark:bg-slate-700 shrink-0"></div>
                        <div class="size-12 rounded-lg bg-slate-200 dark:bg-slate-700 shrink-0"></div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="h-4 w-32 rounded bg-slate-200 dark:bg-slate-700"></div>
                                <div class="h-3 w-12 rounded bg-slate-200 dark:bg-slate-700"></div>
                            </div>
                            <div class="h-3 w-48 rounded bg-slate-200 dark:bg-slate-700"></div>
                            <div class="h-2 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="h-6 w-16 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                            <div class="h-8 w-20 rounded-lg bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
                            <div class="h-8 w-20 rounded-lg bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
                            <div class="size-[30px] rounded-lg bg-slate-200 dark:bg-slate-700"></div>
                        </div>
                    </div>
                @endfor
            </div>

            <div wire:loading.remove wire:target="loadGroup"
                 x-data="{ limit: 30 }"
                 @scroll="if($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 200) limit += 30"
                 class="flex flex-col gap-2 p-3 stagger-fade-in max-h-[800px] overflow-y-auto">
                @foreach ($this->filteredChannels() as $channel)
                    @php
                        $isDead = $channel['status'] === 'DEAD';
                        $isBlocked = $channel['status'] === 'BLOCKED';
                        $containerClass = 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-900/10 hover:shadow-emerald-200/40 hover:border-emerald-300';
                        if ($isDead) $containerClass = 'border-rose-200 bg-rose-50/50 dark:border-rose-900/40 dark:bg-rose-900/10 hover:shadow-rose-200/40 hover:border-rose-300';
                        elseif ($isBlocked) $containerClass = 'border-orange-200 bg-orange-50/40 dark:border-orange-900/40 dark:bg-orange-900/10 hover:shadow-orange-200/40 hover:border-orange-300';
                    @endphp

                    <div wire:key="channel-{{ $channel['id'] }}"
                         x-show="(!showFavoritesOnly || isFav('{{ $channel['url'] }}')) && {{ $loop->index }} < limit"
                         draggable="true"
                         data-channel="{{ json_encode(['id' => $channel['id'], 'url' => $channel['url'], 'name' => $channel['name'], 'category' => $channel['category'], 'logo' => $channel['logo']]) }}"
                         ondragstart="event.dataTransfer.setData('application/json', this.dataset.channel); event.dataTransfer.effectAllowed = 'copy';"
                         class="flex items-center gap-3 rounded-xl border p-3 group transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-md cursor-grab active:cursor-grabbing {{ $containerClass }}">

                        <input type="checkbox" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-600 dark:focus:ring-offset-slate-900 cursor-pointer shrink-0 transition-transform active:scale-90">

                        <div class="text-slate-300 hover:text-slate-500 dark:text-slate-600 transition-colors hidden sm:block">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/></svg>
                        </div>

                        <div class="grid size-12 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white p-1 shadow-sm overflow-hidden dark:border-slate-700 dark:bg-slate-800 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-1">
                            @if(str_starts_with($channel['logo'], 'http'))
                                <img src="{{ $channel['logo'] }}" alt="Logo" class="size-full object-contain p-0.5 transition-transform duration-500 group-hover:scale-110">
                            @else
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ $channel['logo'] }}</span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate text-sm font-extrabold text-slate-800 dark:text-slate-200 transition-colors group-hover:text-indigo-700 dark:group-hover:text-indigo-400">{{ $channel['name'] }}</h3>
                                <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 shadow-sm">{{ $channel['category'] }}</span>

                                @if($channel['is_http'])
                                    <span class="rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-800 border border-orange-200/50 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-800/50 shadow-sm animate-pulse">HTTP (mixed)</span>
                                @endif
                                @if($channel['proxy_needed'])
                                    <span class="rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-800 border border-orange-200/50 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-800/50 shadow-sm">proxy লাগবে</span>
                                @endif
                            </div>

                            <p class="truncate text-[11px] font-medium text-slate-500 mt-0.5 dark:text-slate-400" title="{{ $channel['url'] }}">{{ $channel['url'] }}</p>
                            <p class="text-[10px] font-medium text-slate-400 mt-0.5 dark:text-slate-500">৬:৫৪:২৫ AM</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">

                            <button @click.stop="toggleFav('{{ $channel['url'] }}')"
                                    class="grid size-[30px] place-items-center cursor-pointer rounded-lg border transition-all duration-200 hover:-translate-y-0.5 active:scale-95 shadow-sm"
                                    :class="isFav('{{ $channel['url'] }}') ? 'bg-rose-50 border-rose-200 text-rose-500 dark:bg-rose-900/30 dark:border-rose-900/50 dark:text-rose-400' : 'bg-white border-slate-200 text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-slate-700'">
                                <svg x-show="!isFav('{{ $channel['url'] }}')" class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                <svg x-show="isFav('{{ $channel['url'] }}')" class="size-3.5" fill="currentColor" viewBox="0 0 24 24" style="display: none;"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </button>

                            @if($isDead)
                                <span class="flex items-center gap-1.5 rounded-full bg-rose-100/80 px-2.5 py-1 cursor-default text-[11px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/50 shadow-sm">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    DEAD
                                </span>
                                <button wire:click="verifyChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Retry
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 hover:-translate-y-0.5 active:scale-95 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    ডিলিট
                                </button>
                            @elseif($isBlocked)
                                <span class="flex items-center gap-1.5 rounded-full bg-orange-100/80 px-2.5 py-1 cursor-default text-[11px] font-bold text-orange-700 dark:bg-orange-900/40 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/50 shadow-sm">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    BLOCKED
                                </span>
                                <button wire:click="playChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-indigo-600 hover:shadow-md hover:shadow-indigo-200/40 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play
                                </button>
                                <button onclick="copyToClipboard('{{ $channel['url'] }}')" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg> Copy
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="grid size-[30px] place-items-center cursor-pointer rounded-lg border border-rose-200 bg-white text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 hover:text-rose-600 hover:-translate-y-0.5 active:scale-95 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @else
                                <span class="flex items-center gap-1.5 rounded-full bg-emerald-100/80 px-2.5 py-1 cursor-default text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50 shadow-sm">
                                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span> চলবে
                                </span>
                                <button wire:click="playChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-indigo-600 hover:shadow-md hover:shadow-indigo-200/40 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-400">
                                    <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play
                                </button>
                                <button onclick="copyToClipboard('{{ $channel['url'] }}')" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:-translate-y-0.5 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg> Copy
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="grid size-[30px] place-items-center cursor-pointer rounded-lg border border-rose-200 bg-white text-rose-500 shadow-sm transition-all duration-200 hover:bg-rose-50 hover:text-rose-600 hover:-translate-y-0.5 active:scale-95 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if(count($this->filteredChannels()) === 0)
                    <div class="py-10 text-center text-slate-500 dark:text-slate-400">
                        <svg class="mx-auto size-10 opacity-30 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <p class="text-sm font-semibold tracking-wide">কোনো চ্যানেল পাওয়া যায়নি</p>
                    </div>
                @endif
            </div>
        </section>

        <footer class="dim-in-theater pb-8 flex flex-col items-center justify-center text-center text-xs font-bold text-slate-500 mt-6">
            <p class="uppercase tracking-[0.3em] text-slate-400">Developed by</p>
            <h3 class="mt-1 text-slate-700 dark:text-slate-200 transition-colors hover:text-indigo-600 cursor-pointer tracking-wide">Habibur Rahaman</h3>
            <a class="mt-3 block transition-transform duration-300 hover:scale-110" href="https://www.facebook.com/creativehabib" target="_blank" rel="noopener noreferrer" aria-label="Habibur Rahaman — Facebook">
                <img src="https://i.postimg.cc/pdxGV302/habib-nu-(1).png" alt="Habibur Rahaman" loading="lazy" class="size-16 rounded-full object-cover shadow-lg shadow-violet-300/50 ring-2 ring-violet-100 dark:ring-slate-700">
            </a>
        </footer>

        <template x-teleport="#video-container">
            <div class="fs-sidebar-wrapper">
                <div class="fs-sidebar-trigger" title="Show Channels"></div>
                <div class="fs-sidebar-panel">

                    <div class="px-5 py-4 border-b border-white/10 bg-slate-900/40 flex items-center justify-between">
                        <div class="text-[13px] font-extrabold text-white tracking-widest flex items-center gap-2">
                            <svg class="size-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                            CHANNELS
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="showFavoritesOnly = !showFavoritesOnly"
                                    :class="showFavoritesOnly ? 'bg-rose-500/80 text-white' : 'bg-white/10 text-slate-300 hover:bg-white/20'"
                                    class="p-1 rounded transition-colors" title="Toggle Favorites">
                                <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </button>
                        </div>
                    </div>

                    <div x-data="{ fsLimit: 30 }"
                         @scroll="if($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100) fsLimit += 30"
                         class="flex-1 overflow-y-auto p-2 space-y-1">
                        @foreach ($this->filteredChannels() as $channel)
                            <div x-show="(!showFavoritesOnly || isFav('{{ $channel['url'] }}')) && {{ $loop->index }} < fsLimit"
                                 draggable="true"
                                 data-channel="{{ json_encode(['id' => $channel['id'], 'url' => $channel['url'], 'name' => $channel['name'], 'category' => $channel['category'], 'logo' => $channel['logo']]) }}"
                                 ondragstart="event.dataTransfer.setData('application/json', this.dataset.channel); event.dataTransfer.effectAllowed = 'copy';"
                                 class="w-full text-left flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/10 transition-all duration-200 group/fsbtn active:scale-95 cursor-grab">

                                <div wire:click="playChannel({{ $channel['id'] }})" class="flex items-center gap-3 flex-1">
                                    <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-white/5 border border-white/10 overflow-hidden shadow-sm group-hover/fsbtn:border-indigo-500/50 transition-colors">
                                        @if(str_starts_with($channel['logo'], 'http'))
                                            <img src="{{ $channel['logo'] }}" class="size-full object-contain p-0.5" loading="lazy" alt="Logo">
                                        @else
                                            <span class="text-[10px] font-black text-indigo-300">{{ $channel['logo'] }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-[13px] font-bold text-slate-200 group-hover/fsbtn:text-white transition-colors">{{ $channel['name'] }}</div>
                                        <div class="text-[10px] font-medium text-slate-400 mt-0.5">{{ $channel['category'] }}</div>
                                    </div>
                                </div>

                                <button @click.stop="toggleFav('{{ $channel['url'] }}')" class="p-1 hover:bg-white/20 rounded-full transition-colors shrink-0">
                                    <svg x-show="!isFav('{{ $channel['url'] }}')" class="size-4 text-slate-400 hover:text-rose-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    <svg x-show="isFav('{{ $channel['url'] }}')" class="size-4 text-rose-500" fill="currentColor" viewBox="0 0 24 24" style="display: none;"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </template>
    </div>

    <div id="toast-container" class="fixed bottom-5 left-1/2 z-50 flex -translate-x-1/2 flex-col gap-2 pointer-events-none"></div>

    <script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
    <script>
        // CHROMECAST INITIALIZATION
        window['__onGCastApiAvailable'] = function(isAvailable) {
            if (isAvailable) {
                cast.framework.CastContext.getInstance().setOptions({
                    receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
                    autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
                });
                const castBtn = document.getElementById('cast-btn');
                if(castBtn) castBtn.classList.remove('hidden');
            }
        };

        // JS দিয়ে Fullscreen ক্লাস অ্যাড/রিমুভ করার জন্য
        const fsEvents = ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'];
        fsEvents.forEach(evt => document.addEventListener(evt, () => {
            const vc = document.getElementById('video-container');
            if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
                vc.classList.add('video-is-fullscreen');
            } else {
                vc.classList.remove('video-is-fullscreen');
            }
        }));

        window.showToast = function(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if(!toastContainer) return;
            const toast = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-emerald-500/95' : 'bg-rose-500/95';
            toast.className = `flex items-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-full shadow-[0_8px_30px_rgb(0,0,0,0.12)] transition-all duration-300 transform translate-y-10 opacity-0 ${bgClass} backdrop-blur-md z-[200]`;
            toast.innerHTML = type === 'success'
                ? `<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> <span>${message}</span>`
                : `<span>${message}</span>`;
            toastContainer.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        window.copyToClipboard = function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => window.showToast('Link Copied!')).catch(() => window.showToast('Failed!', 'error'));
            } else {
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed"; textArea.style.left = "-999999px";
                document.body.appendChild(textArea); textArea.focus(); textArea.select();
                try { document.execCommand('copy'); window.showToast('Link Copied!'); }
                catch (err) { window.showToast('Failed!', 'error'); }
                textArea.remove();
            }
        };

        // DRAG AND DROP HANDLER
        window.handleDrop = function(event, slotId) {
            const data = event.dataTransfer.getData('application/json');
            if(data) {
                try {
                    const parsed = JSON.parse(data);
                    setActiveSlot(slotId);

                    if (window.loadStream) {
                        window.loadStream(parsed.url, parsed.name, slotId);
                    }

                    const titleEl = document.getElementById('active-channel-title');
                    if(titleEl) titleEl.innerHTML = `${parsed.name} <span class="font-semibold text-slate-500">· ${parsed.category}</span>`;

                    const logoEl = document.getElementById('active-channel-logo');
                    if(logoEl) {
                        if(parsed.logo.startsWith('http')) logoEl.innerHTML = `<img src="${parsed.logo}" alt="Logo" class="size-full object-contain p-0.5">`;
                        else logoEl.innerHTML = parsed.logo;
                    }

                    window.showToast(`Screen ${slotId}-এ ${parsed.name} প্লে হচ্ছে`);
                } catch(e) { console.error("Drag parsing error", e); }
            }
        };

        // FLOATING PLAYER (MINI PLAYER) LOGIC
        window.addEventListener('scroll', () => {
            const placeholder = document.getElementById('video-placeholder');
            const container = document.getElementById('video-container');
            if (!placeholder || !container) return;

            const rect = placeholder.getBoundingClientRect();
            // If scrolled past the video and not in fullscreen/theater mode
            if (rect.bottom < 0 && !document.fullscreenElement && !document.body.classList.contains('theater-mode-active')) {
                container.classList.add('floating-player');
            } else {
                container.classList.remove('floating-player');
            }
        });

        window.toggleShortcutsModal = function() {
            const modal = document.getElementById('shortcuts-modal');
            if(modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                setTimeout(() => { modal.classList.remove('opacity-0'); modal.querySelector('.glass-menu').classList.remove('scale-95'); }, 10);
            } else {
                modal.classList.add('opacity-0'); modal.querySelector('.glass-menu').classList.add('scale-95');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        };

        window.hideContextMenu = function() {
            const menu = document.getElementById('custom-context-menu');
            menu.classList.add('opacity-0', 'scale-95');
            setTimeout(() => menu.classList.add('hidden'), 200);
        };

        window.toggleStats = function() {
            const box = document.getElementById('stats-box');
            box.classList.toggle('hidden');
        };

        window.toggleLoop = function() {
            const v = document.getElementById('player-' + activeSlot);
            if(v) {
                v.loop = !v.loop;
                document.getElementById('loop-status').textContent = v.loop ? 'On' : 'Off';
                window.showToast(v.loop ? 'Video Looping Enabled' : 'Video Looping Disabled');
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const videoContainer = document.getElementById('video-container');
            const contextMenu = document.getElementById('custom-context-menu');
            videoContainer.addEventListener('contextmenu', (e) => {
                e.preventDefault(); contextMenu.classList.remove('hidden');
                let x = e.offsetX; let y = e.offsetY;
                if(x + contextMenu.offsetWidth > videoContainer.offsetWidth) x = videoContainer.offsetWidth - contextMenu.offsetWidth;
                if(y + contextMenu.offsetHeight > videoContainer.offsetHeight) y = videoContainer.offsetHeight - contextMenu.offsetHeight;
                contextMenu.style.left = `${x}px`; contextMenu.style.top = `${y}px`;
                setTimeout(() => { contextMenu.classList.remove('opacity-0', 'scale-95'); }, 10);
            });
            document.addEventListener('click', (e) => {
                if(!contextMenu.contains(e.target)) hideContextMenu();
            });
            const theaterBtn = document.getElementById('theater-btn');
            if(theaterBtn) {
                theaterBtn.addEventListener('click', () => {
                    document.body.classList.toggle('theater-mode-active');
                    const isTheater = document.body.classList.contains('theater-mode-active');
                    window.showToast(isTheater ? 'Theater Mode ON' : 'Theater Mode OFF');
                    if (isTheater) {
                        const playerSection = document.querySelector('.player-section-wrapper');
                        if (playerSection) window.scrollTo({ top: playerSection.offsetTop - 20, behavior: 'smooth' });
                    }
                });
            }
        });
    </script>

    @script
    <script>
        let activeSlot = 1; let currentLayout = 1;
        let hlsPlayers = {1: null, 2: null, 3: null, 4: null}; let shakaPlayers = {1: null, 2: null, 3: null, 4: null}; let retryCounts = {1: 0, 2: 0, 3: 0, 4: 0};
        const MAX_RETRIES = 3;

        // CHROMECAST CURRENT STREAM TRACKING
        window.currentStreamUrls = {1: null, 2: null, 3: null, 4: null};
        window.currentStreamNames = {1: null, 2: null, 3: null, 4: null};

        const customControls = document.getElementById('custom-controls'); const playPauseBtn = document.getElementById('play-pause-btn'); const muteBtn = document.getElementById('mute-btn'); const volumeSlider = document.getElementById('volume-slider'); const pipBtn = document.getElementById('pip-btn'); const airplayBtn = document.getElementById('airplay-btn'); const castBtn = document.getElementById('cast-btn'); const fullscreenBtn = document.getElementById('fullscreen-btn'); const layoutBtn = document.getElementById('layout-btn'); const qualityList = document.getElementById('quality-list'); const topFullscreenBtn = document.getElementById('top-fullscreen-btn'); const topStopBtn = document.getElementById('top-stop-btn');

        const playIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>`;
        const pauseIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;
        const volHighIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>`;
        const volMuteIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>`;

        window.getActiveVideo = function() { return document.getElementById('player-' + activeSlot); }

        window.setActiveSlot = function(slot) {
            for(let i=1; i<=4; i++) {
                const slotEl = document.getElementById('slot-'+i);
                slotEl.classList.remove('slot-active'); slotEl.classList.add('slot-inactive');
                const vid = document.getElementById('player-'+i);
                if(i !== slot) vid.muted = true;
            }
            activeSlot = slot;
            const newSlotEl = document.getElementById('slot-'+slot);
            newSlotEl.classList.remove('slot-inactive'); newSlotEl.classList.add('slot-active');

            const activeVideo = getActiveVideo();
            if(activeVideo.volume === 0 && !activeVideo.muted) activeVideo.volume = 0.5;
            playPauseBtn.innerHTML = activeVideo.paused ? playIcon : pauseIcon;
            volumeSlider.value = activeVideo.muted ? 0 : activeVideo.volume;
            muteBtn.innerHTML = activeVideo.muted || activeVideo.volume === 0 ? volMuteIcon : volHighIcon;

            if (window.WebKitPlaybackTargetAvailabilityEvent && airplayBtn.dataset.available === 'true') { airplayBtn.classList.remove('hidden'); }
            updateQualityMenu(activeSlot);
        };

        layoutBtn.addEventListener('click', () => {
            const grid = document.getElementById('video-grid');
            if (currentLayout === 1) {
                currentLayout = 2; grid.className = 'absolute inset-0 grid grid-cols-2 grid-rows-1 gap-[2px] bg-slate-900 transition-all duration-300';
                document.getElementById('slot-2').classList.remove('hidden'); window.showToast('Split Screen Mode: 2 Players');
            } else if (currentLayout === 2) {
                currentLayout = 4; grid.className = 'absolute inset-0 grid grid-cols-2 grid-rows-2 gap-[2px] bg-slate-900 transition-all duration-300';
                document.getElementById('slot-3').classList.remove('hidden'); document.getElementById('slot-4').classList.remove('hidden'); window.showToast('Grid Mode: 4 Players');
            } else {
                currentLayout = 1; grid.className = 'absolute inset-0 grid grid-cols-1 grid-rows-1 gap-[2px] bg-slate-900 transition-all duration-300';
                document.getElementById('slot-2').classList.add('hidden'); document.getElementById('slot-3').classList.add('hidden'); document.getElementById('slot-4').classList.add('hidden');
                if (activeSlot > 1) setActiveSlot(1); window.showToast('Single Player Mode');
            }
        });

        let idleTimer; let lastMouseX = 0; let lastMouseY = 0; const videoContainer = document.getElementById('video-container');

        function showControls() {
            videoContainer.style.cursor = 'default'; customControls.style.opacity = '1'; customControls.style.pointerEvents = 'auto';
            clearTimeout(idleTimer); if (!getActiveVideo().paused) idleTimer = setTimeout(hideControls, 2500);
        }

        function hideControls() {
            if (!getActiveVideo().paused) {
                videoContainer.style.cursor = 'none'; customControls.style.opacity = '0'; customControls.style.pointerEvents = 'none'; window.hideContextMenu();
            }
        }

        videoContainer.addEventListener('mousemove', (e) => { if (e.clientX === lastMouseX && e.clientY === lastMouseY) return; lastMouseX = e.clientX; lastMouseY = e.clientY; showControls(); });
        videoContainer.addEventListener('mousedown', showControls); videoContainer.addEventListener('touchstart', showControls); videoContainer.addEventListener('mouseleave', hideControls); customControls.addEventListener('mouseenter', () => clearTimeout(idleTimer)); customControls.addEventListener('mouseleave', showControls);

        for (let i = 1; i <= 4; i++) {
            const v = document.getElementById('player-' + i); const spinner = document.getElementById('spinner-' + i);
            v.addEventListener('waiting', () => { spinner.style.opacity = '1'; }); v.addEventListener('playing', () => { spinner.style.opacity = '0'; }); v.addEventListener('canplay', () => { spinner.style.opacity = '0'; });
            v.addEventListener('play', () => { if (activeSlot === i) playPauseBtn.innerHTML = pauseIcon; showControls(); });
            v.addEventListener('pause', () => { if (activeSlot === i) playPauseBtn.innerHTML = playIcon; showControls(); });
            v.addEventListener('volumechange', () => {
                if (activeSlot === i) {
                    if (v.muted || v.volume === 0) { muteBtn.innerHTML = volMuteIcon; volumeSlider.value = 0; }
                    else { muteBtn.innerHTML = volHighIcon; volumeSlider.value = v.volume; }
                }
            });
            v.addEventListener('dblclick', async () => {
                if (document.fullscreenElement || document.webkitFullscreenElement) { if (document.exitFullscreen) await document.exitFullscreen(); else if (document.webkitExitFullscreen) await document.webkitExitFullscreen(); }
                else { if (videoContainer.requestFullscreen) await videoContainer.requestFullscreen(); else if (videoContainer.webkitRequestFullscreen) await videoContainer.webkitRequestFullscreen(); }
            });
            v.addEventListener('click', (e) => {
                if (e.target !== customControls && !customControls.contains(e.target)) { setActiveSlot(i); if (v.paused) v.play(); else v.pause(); }
            });
            if (window.WebKitPlaybackTargetAvailabilityEvent) {
                v.addEventListener('webkitplaybacktargetavailabilitychanged', function(event) {
                    if (event.availability === 'available') { airplayBtn.dataset.available = 'true'; if (activeSlot === i) airplayBtn.classList.remove('hidden'); }
                });
            }
            setInterval(() => {
                if(activeSlot === i && !document.getElementById('stats-box').classList.contains('hidden')) {
                    document.getElementById('stat-res').textContent = `${v.videoWidth}x${v.videoHeight}`;
                    if(v.webkitDroppedFrameCount !== undefined) document.getElementById('stat-frames').textContent = v.webkitDroppedFrameCount;
                }
            }, 1000);
        }

        playPauseBtn.addEventListener('click', () => { const v = getActiveVideo(); if (v.paused) v.play(); else v.pause(); });
        topStopBtn.addEventListener('click', () => { const v = getActiveVideo(); v.pause(); updateStatus(activeSlot, 'Stopped'); showControls(); });
        muteBtn.addEventListener('click', () => { const v = getActiveVideo(); v.muted = !v.muted; if(!v.muted && v.volume === 0) v.volume = 0.5; volumeSlider.value = v.muted ? 0 : v.volume; });
        volumeSlider.addEventListener('input', (e) => { const v = getActiveVideo(); v.volume = e.target.value; v.muted = e.target.value === '0'; });
        if (!document.pictureInPictureEnabled) pipBtn.style.display = 'none';
        pipBtn.addEventListener('click', async () => { const v = getActiveVideo(); try { if (document.pictureInPictureElement) await document.exitPictureInPicture(); else await v.requestPictureInPicture(); } catch (err) { console.error(err); } });
        const toggleFs = async () => {
            if (document.fullscreenElement || document.webkitFullscreenElement) { if (document.exitFullscreen) await document.exitFullscreen(); else if (document.webkitExitFullscreen) await document.webkitExitFullscreen(); }
            else { if (videoContainer.requestFullscreen) await videoContainer.requestFullscreen(); else if (videoContainer.webkitRequestFullscreen) await videoContainer.webkitRequestFullscreen(); }
        };
        fullscreenBtn.addEventListener('click', toggleFs); topFullscreenBtn.addEventListener('click', toggleFs);
        airplayBtn.addEventListener('click', () => { const v = getActiveVideo(); if(v.webkitShowPlaybackTargetPicker) v.webkitShowPlaybackTargetPicker(); });

        // CHROMECAST CLICK HANDLER
        if(castBtn) {
            castBtn.addEventListener('click', () => {
                const context = cast.framework.CastContext.getInstance();
                const session = context.getCurrentSession();
                if (!session) {
                    context.requestSession().then(loadMedia, function(error) {
                        if (error !== 'cancel') window.showToast('Cast Error: ' + error, 'error');
                    });
                } else {
                    loadMedia();
                }

                function loadMedia() {
                    const session = cast.framework.CastContext.getInstance().getCurrentSession();
                    if(!session) return;

                    const videoUrl = window.currentStreamUrls[activeSlot];
                    const videoName = window.currentStreamNames[activeSlot] || 'Live Stream';

                    if(!videoUrl) {
                        window.showToast('No active stream to cast', 'error');
                        return;
                    }

                    const contentType = videoUrl.includes('.mpd') ? 'application/dash+xml' : 'application/x-mpegurl';
                    const mediaInfo = new chrome.cast.media.MediaInfo(videoUrl, contentType);

                    const metadata = new chrome.cast.media.GenericMediaMetadata();
                    metadata.title = videoName;
                    mediaInfo.metadata = metadata;

                    const request = new chrome.cast.media.LoadRequest(mediaInfo);

                    session.loadMedia(request).then(
                        function() { window.showToast('Casting: ' + videoName); },
                        function(error) { window.showToast('Error casting media.', 'error'); }
                    );
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
            const v = getActiveVideo();
            switch(e.key) { case '?': e.preventDefault(); window.toggleShortcutsModal(); break; case 'Escape': window.hideContextMenu(); document.getElementById('stats-box').classList.add('hidden'); document.getElementById('shortcuts-modal').classList.add('hidden'); break; }
            switch(e.key.toLowerCase()) { case ' ': case 'k': e.preventDefault(); if (v.paused) v.play(); else v.pause(); showControls(); break; case 'm': e.preventDefault(); muteBtn.click(); showControls(); break; case 'f': e.preventDefault(); toggleFs(); showControls(); break; case 't': e.preventDefault(); const theaterBtn = document.getElementById('theater-btn'); if(theaterBtn) theaterBtn.click(); break; }
        });

        function updateStatus(slotId, message) { const statusEl = document.getElementById('status-' + slotId); if (statusEl) { statusEl.textContent = message; statusEl.style.opacity = '1'; setTimeout(() => { statusEl.style.opacity = '0'; }, 3000); } }

        window.setQuality = function(levelIndex) { const hls = hlsPlayers[activeSlot]; if(hls) { hls.currentLevel = levelIndex; updateQualityMenu(activeSlot); window.showToast(levelIndex === -1 ? 'Auto Quality Selected' : 'Quality Changed'); } };

        function updateQualityMenu(slotId) {
            const hls = hlsPlayers[slotId];
            if(hls && hls.levels && hls.levels.length > 0) {
                let html = `<button onclick="setQuality(-1)" class="text-left px-4 py-1.5 hover:bg-white/20 transition flex items-center gap-2 text-indigo-300">${hls.currentLevel === -1 ? '<span class="size-1.5 rounded-full bg-indigo-500"></span>' : '<span class="size-1.5"></span>'} Auto</button>`;
                hls.levels.forEach((level, index) => { html += `<button onclick="setQuality(${index})" class="text-left px-4 py-1.5 hover:bg-white/20 transition flex items-center gap-2">${hls.currentLevel === index ? '<span class="size-1.5 rounded-full bg-white"></span>' : '<span class="size-1.5"></span>'} ${level.height}p</button>`; });
                qualityList.innerHTML = html;
            } else { qualityList.innerHTML = '<div class="px-4 py-1.5 text-slate-500 italic">Not available</div>'; }
        }

        function isHlsStream(url) { return /\.m3u8?(\?|#|$)/i.test(url) || /m3u8/i.test(url); }
        function isDashStream(url) { return /\.mpd(\?|#|$)/i.test(url); }
        function loadScript(src, globalName) {
            if (window[globalName]) return Promise.resolve();
            const existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) return new Promise((resolve, reject) => { existingScript.addEventListener('load', () => resolve(), { once: true }); existingScript.addEventListener('error', () => reject(new Error(`Unable to load ${src}`)), { once: true }); });
            return new Promise((resolve, reject) => { const script = document.createElement('script'); script.src = src; script.onload = () => resolve(); script.onerror = () => reject(new Error(`Unable to load ${src}`)); document.head.appendChild(script); });
        }

        function resetPlayerForSlot(slotId) {
            if (hlsPlayers[slotId]) { hlsPlayers[slotId].destroy(); hlsPlayers[slotId] = null; }
            if (shakaPlayers[slotId]) { shakaPlayers[slotId].unload().catch(() => {}); }
            const v = document.getElementById('player-' + slotId);
            if (v) { v.removeAttribute('src'); v.load(); }
            document.getElementById('spinner-' + slotId).style.opacity = '0';
            updateQualityMenu(slotId);
        }

        window.loadStream = async function(url, name = 'Live stream', targetSlot = activeSlot) {
            // Update tracking for Chromecast
            window.currentStreamUrls[targetSlot] = url;
            window.currentStreamNames[targetSlot] = name;

            const v = document.getElementById('player-' + targetSlot);
            if (!v || !url) return;
            v.muted = targetSlot !== activeSlot;
            try {
                if (isHlsStream(url)) { await playWithHlsJs(url, name, targetSlot); return; }
                if (isDashStream(url)) { await playWithShaka(url, name, targetSlot); return; }
                await playNative(url, name, targetSlot);
            } catch (error) {
                if (isHlsStream(url)) {
                    updateStatus(targetSlot, `Fallback native...`);
                    await playNative(url, name, targetSlot).catch(() => updateStatus(targetSlot, `Error: ${error.code ?? error.message ?? 'unknown'}`));
                    return;
                }
                updateStatus(targetSlot, `Error: ${error.code ?? error.message ?? 'unknown'}`);
            }
        }

        async function playNative(url, name, slotId) {
            resetPlayerForSlot(slotId); const v = document.getElementById('player-' + slotId); v.src = url; updateStatus(slotId, `Loading ${name}...`); document.getElementById('spinner-' + slotId).style.opacity = '1';
            await v.play().catch(() => updateStatus(slotId, 'Tap play to start'));
        }

        async function playWithHlsJs(url, name, slotId) {
            hlsLoader ??= loadScript('https://cdn.jsdelivr.net/npm/hls.js@1.6.14/dist/hls.min.js', 'Hls'); await hlsLoader;
            if (!window.Hls || !Hls.isSupported()) { await playNative(url, name, slotId); return; }
            resetPlayerForSlot(slotId); const v = document.getElementById('player-' + slotId); const spinner = document.getElementById('spinner-' + slotId);
            const hls = new Hls({ enableWorker: true, lowLatencyMode: true }); hlsPlayers[slotId] = hls;
            hls.on(Hls.Events.ERROR, (event, data) => {
                if (!data.fatal) return;
                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                    if (retryCounts[slotId] < MAX_RETRIES) { retryCounts[slotId]++; updateStatus(slotId, `Reconnecting (${retryCounts[slotId]}/${MAX_RETRIES})...`); spinner.style.opacity = '1'; setTimeout(() => hls.startLoad(), 3000); }
                    else { updateStatus(slotId, `Stream offline.`); spinner.style.opacity = '0'; hls.destroy(); hlsPlayers[slotId] = null; } return;
                }
                if (data.type === Hls.ErrorTypes.MEDIA_ERROR) { updateStatus(slotId, `Recovering media...`); hls.recoverMediaError(); return; }
                updateStatus(slotId, `Load failed`); hls.destroy(); hlsPlayers[slotId] = null; playNative(url, name, slotId).catch(() => updateStatus(slotId, `Error: ${data.details ?? 'unknown'}`));
            });
            hls.on(Hls.Events.MANIFEST_PARSED, async () => { retryCounts[slotId] = 0; updateStatus(slotId, `Playing: ${name}`); updateQualityMenu(slotId); await v.play().catch(() => updateStatus(slotId, 'Tap play to start')); });
            hls.on(Hls.Events.FRAG_LOADED, (event, data) => { if(activeSlot === slotId) { const bw = Math.round(data.frag.stats.bwEstimate / 1000); if(bw) document.getElementById('stat-bw').textContent = bw + ' Kbps'; } });
            updateStatus(slotId, `Loading ${name}...`); spinner.style.opacity = '1'; hls.loadSource(url); hls.attachMedia(v);
        }

        async function playWithShaka(url, name, slotId) {
            shakaLoader ??= loadScript('https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.15.9/shaka-player.compiled.min.js', 'shaka'); await shakaLoader;
            resetPlayerForSlot(slotId); shaka.polyfill.installAll(); const v = document.getElementById('player-' + slotId); const spinner = document.getElementById('spinner-' + slotId);
            const shakaP = new shaka.Player(v); shakaPlayers[slotId] = shakaP;
            shakaP.addEventListener('error', (event) => { updateStatus(slotId, `Error: ${event.detail.code}`); spinner.style.opacity = '0'; });
            updateStatus(slotId, `Loading ${name}...`); spinner.style.opacity = '1'; await shakaP.load(url); updateStatus(slotId, `Playing: ${name}`); await v.play().catch(() => updateStatus(slotId, 'Tap play to start'));
        }

        // Livewire init hook
        window.addEventListener('load', () => {
            loadStream(@js($this->selectedChannel()['url']), @js($this->selectedChannel()['name']));
        });
        $wire.on('stream-selected', ({ url, name }) => loadStream(url, name));
    </script>
    @endscript
</div>
