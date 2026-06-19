<?php

use Illuminate\Support\Facades\Http;
use Livewire\Component;

new class extends Component {
    public string $singleStreamUrl = 'https://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8';

    public string $playlist = '';

    public string $search = '';

    public string $activeGroupKey = 'techeasylife';

    public int $selectedChannelId = 1;

    public ?string $loadMessage = null;

    /**
     * @var list<array{key:string,name:string,badge:string,url:string}>
     */
    public array $groups = [
        ['key' => 'techeasylife', 'name' => 'TechEasyLife 👑', 'badge' => 'BD', 'url' => 'https://raw.githubusercontent.com/Monjil404/livetv/refs/heads/main/pro'],
        ['key' => 'sports', 'name' => 'Sports ⚽', 'badge' => 'SPORTS', 'url' => 'https://raw.githubusercontent.com/Monjil404/TVspo/refs/heads/main/tvs'],
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
            // Check if the stream is accessible
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

    /**
     * @return array
     */
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

    /**
     * @return list<array>
     */
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

            // --- SMART STATUS LOGIC BASED ON URL ---
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
<div class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,#d8dcff_0,#f9f7ff_28%,#f2ecff_48%,#eafbf8_100%)] text-slate-800 dark:bg-slate-950 dark:text-slate-100 relative">

    <style>
        /* Custom Volume Slider CSS */
        input[type=range].custom-vol {
            -webkit-appearance: none;
            background: rgba(255, 255, 255, 0.4);
            height: 4px;
            border-radius: 2px;
            outline: none;
        }
        input[type=range].custom-vol::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 12px;
            width: 12px;
            border-radius: 50%;
            background: #ffffff;
            cursor: pointer;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
            transition: transform 0.1s;
        }
        input[type=range].custom-vol::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
    </style>

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-5 px-4 py-6 sm:py-8">
        <header class="text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-2 text-sm font-bold text-violet-700 shadow-lg shadow-violet-200/60 ring-1 ring-violet-100">
                <span class="grid size-7 place-items-center rounded-xl bg-gradient-to-br from-violet-600 to-sky-500 text-white">▶</span>
                LiveTVPro
            </div>
            <p class="mt-1 text-xs font-medium uppercase tracking-[0.28em] text-slate-400">HLS / DASH / M3U8 Live Streaming Portal</p>
            <div class="mt-3 flex justify-center gap-2 text-[11px] font-semibold">
                <span class="rounded-full bg-white/80 px-3 py-1 text-violet-600 shadow-sm">● Groups: {{ count($groups) }}</span>
                <span class="rounded-full bg-sky-500 px-3 py-1 text-white shadow-sm">Join Telegram</span>
            </div>
        </header>

        <section class="rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700">
            <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wider text-violet-600">
                <span>● Single stream URL</span>
                <span class="text-slate-400">HLS / DASH / HTML5</span>
            </div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input wire:model="singleStreamUrl" class="min-h-11 flex-1 rounded-xl border border-violet-100 bg-violet-50/40 px-3 text-xs outline-none ring-violet-300 transition focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="https://example.com/stream.m3u8">
                <button wire:click="playSingleStream" class="rounded-xl bg-violet-600 px-5 py-3 cursor-pointer text-xs font-bold text-white shadow-lg shadow-violet-300 transition hover:bg-violet-700">▶ Play</button>
                <button type="button" class="rounded-xl border border-violet-100 bg-white px-4 py-3 cursor-pointer text-xs font-bold text-violet-600 shadow-sm dark:border-slate-700 dark:bg-slate-800">ⓘ Check</button>
            </div>
            <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-violet-600">Playlist / M3U / HTML embed</label>
            <textarea wire:model="playlist" rows="4" class="mt-2 w-full rounded-xl border border-violet-100 bg-violet-50/40 p-3 text-xs outline-none ring-violet-300 transition focus:ring-2 dark:border-slate-700 dark:bg-slate-800"></textarea>
            <div class="mt-3 flex gap-2">
                <button wire:click="loadGroup('{{ $activeGroupKey }}')" class="rounded-xl bg-violet-600 px-4 py-2 cursor-pointer text-xs font-bold text-white">Reload Group</button>
                <button wire:click="clearInputs" class="rounded-xl bg-slate-100 px-4 py-2 cursor-pointer text-xs font-bold text-slate-500 dark:bg-slate-800">Clear</button>
            </div>
        </section>

        <section class="rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs font-bold uppercase tracking-wider text-violet-600">
                <span>📡 পাবলিক সোর্স — ক্লিক করে লোড + চেক</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-500 dark:bg-slate-800">{{ count($this->filteredChannels()) }} visible</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($groups as $group)
                    <button wire:key="group-{{ $group['key'] }}" wire:click="loadGroup('{{ $group['key'] }}')" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition {{ $activeGroupKey === $group['key'] ? 'border-violet-500 bg-violet-600 text-white shadow-lg shadow-violet-200' : 'border-violet-100 bg-white text-slate-600 hover:border-violet-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                        <span>{{ $group['name'] }}</span>
                        <span class="rounded-md bg-violet-50 px-1.5 py-0.5 text-[10px] font-black uppercase text-violet-600">{{ $group['badge'] }}</span>
                    </button>
                @endforeach
            </div>
            @if ($loadMessage)
                <p class="mt-3 text-xs font-semibold text-slate-500">⚠️ {{ $loadMessage }}</p>
            @endif
            <p class="mt-3 text-[11px] font-semibold text-slate-400">কিছু list (time2shine/Bugsfree) থেকে হাজার+ চ্যানেল আসতে পারে। Axsport referer-locked হলে browser-এ ব্লক দেখাবে, VLC-তে চলতে পারে।</p>
        </section>

        <!-- Player Section -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-violet-200/50 ring-1 ring-white/80 dark:bg-slate-900 dark:ring-slate-700">
            <!-- TOP BAR DESIGN -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-50 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 shrink-0 place-items-center rounded-[10px] border border-slate-200 bg-white overflow-hidden text-xs font-black text-indigo-600 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        @if(str_starts_with($this->selectedChannel()['logo'], 'http'))
                            <img src="{{ $this->selectedChannel()['logo'] }}" alt="Logo" class="size-full object-contain p-0.5">
                        @else
                            {{ $this->selectedChannel()['logo'] }}
                        @endif
                    </div>
                    <div class="flex flex-col justify-center">
                        <h2 class="text-[15px] font-extrabold text-slate-800 dark:text-slate-100 leading-tight">
                            {{ $this->selectedChannel()['name'] }} <span class="font-semibold text-slate-500">· {{ $this->selectedChannel()['category'] }}</span>
                        </h2>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400 tracking-wide">
                                <svg class="size-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M10 9l5 3-5 3z"></path></svg>
                                LIVE PLAYER
                            </div>
                            <span class="flex items-center gap-1.5 rounded-full bg-emerald-100/60 px-2 py-0.5 text-[10px] font-bold text-emerald-600 ring-1 ring-emerald-200/60 dark:bg-emerald-900/30 dark:ring-emerald-800">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                LIVE
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button id="top-fullscreen-btn" class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        Full
                    </button>
                    <button id="top-stop-btn" class="flex items-center gap-1.5 cursor-pointer rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/40 dark:bg-slate-800 dark:hover:bg-rose-900/60">
                        <span class="size-2 rounded-sm bg-rose-500"></span>
                        Stop
                    </button>
                </div>
            </div>

            <div wire:ignore id="video-container" class="relative aspect-video bg-black overflow-hidden transition-all">
                <video id="live-tv-player" class="size-full" autoplay muted playsinline poster="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1400&q=80"></video>

                <div id="player-status" class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-black/70 px-4 py-2 text-xs font-bold text-white shadow-lg backdrop-blur">Shaka Player ready</div>

                <div id="custom-controls" class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/80 via-black/30 to-transparent p-4 px-5 opacity-100 transition-opacity duration-500">
                    <div class="flex items-center gap-4">
                        <button id="play-pause-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30 hover:scale-105 active:scale-95">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>
                        <div class="group/vol flex items-center gap-2">
                            <button id="mute-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30 hover:scale-105 active:scale-95">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
                            </button>
                            <input type="range" id="volume-slider" class="custom-vol w-0 cursor-pointer opacity-0 transition-all duration-300 group-hover/vol:w-20 group-hover/vol:opacity-100" min="0" max="1" step="0.05" value="0">
                        </div>
                        <div class="flex items-center gap-2 font-bold text-white ml-2">
                            <span class="size-2.5 animate-pulse rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,1)]"></span>
                            <span class="text-sm font-extrabold tracking-wider">LIVE</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button id="pip-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30 hover:scale-105 active:scale-95" title="Picture-in-Picture">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4.5" width="20" height="15" rx="2"></rect><rect x="12" y="11" width="8" height="6" rx="1" fill="currentColor" stroke="none"></rect></svg>
                        </button>
                        <button id="fullscreen-btn" class="grid size-10 place-items-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30 hover:scale-105 active:scale-95" title="Fullscreen">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 00-2 2v3M21 8V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3M16 21h3a2 2 0 002-2v-3"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-4 divide-x divide-violet-100 border-t border-violet-100 text-center text-xs dark:divide-slate-700 dark:border-slate-700">
                <div class="p-3"><strong class="block text-violet-600">{{ count($channels) }}</strong><span class="text-slate-400">Total</span></div>
                <div class="p-3"><strong class="block text-emerald-500">{{ count($channels) }}</strong><span class="text-slate-400">Live</span></div>
                <div class="p-3"><strong class="block text-orange-500">0</strong><span class="text-slate-400">Down</span></div>
                <div class="p-3"><strong class="block text-rose-500">0</strong><span class="text-slate-400">Errors</span></div>
            </div>
        </section>

        <!-- CHANNEL LIST WITH SMART STATUSES & SKELETON LOADER -->
        @php
            $visibleChannels = $this->filteredChannels();
            $liveCount = collect($visibleChannels)->whereIn('status', ['LIVE', 'READY'])->count();
            $blockedCount = collect($visibleChannels)->where('status', 'BLOCKED')->count();
            $deadCount = collect($visibleChannels)->where('status', 'DEAD')->count();
        @endphp

        <section class="rounded-2xl bg-white shadow-xl shadow-indigo-200/50 ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-slate-800">

            <!-- Top Action Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <!-- Status Tabs -->
                <div class="flex items-center gap-1 bg-slate-50 p-1.5 rounded-xl dark:bg-slate-800/50">
                    <button class="flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 cursor-pointer text-xs font-bold text-indigo-700 shadow-sm ring-1 ring-slate-200/50 dark:bg-slate-700 dark:text-indigo-300 dark:ring-slate-600 transition">
                        সব <span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">{{ count($visibleChannels) }}</span>
                    </button>
                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200">
                        <span class="size-2 rounded-full bg-emerald-500"></span> চলবে <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $liveCount }}</span>
                    </button>
                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200">
                        <span class="size-2 rounded-full bg-orange-500"></span> ব্লকড <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $blockedCount }}</span>
                    </button>
                    <button class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 cursor-pointer text-xs font-bold text-slate-500 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200">
                        <span class="size-2 rounded-full bg-rose-500"></span> ডেড <span class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $deadCount }}</span>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button class="flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-600 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-900/50 dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-900/30">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg>
                        চলবে কপি
                    </button>
                    <button class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-600 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-900/50 dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-indigo-900/30">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        .m3u
                    </button>
                    <button wire:click="deleteBlockedChannels" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        ব্লকড ডিলিট
                    </button>
                    <button wire:click="deleteDeadChannels" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        ডেড ডিলিট
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="px-4 py-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input wire:model.live="search" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-9 pr-10 text-sm font-medium text-slate-700 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-indigo-500 dark:focus:bg-slate-900" placeholder="নাম, group বা URL দিয়ে সার্চ করুন...">
                    @if($search !== '')
                        <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 grid size-5 -translate-y-1/2 place-items-center rounded-full bg-slate-200 text-slate-500 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-slate-600">
                            <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between px-4 pb-2 pt-1 border-b border-slate-50 dark:border-slate-800">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-500 cursor-pointer hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                    <input type="checkbox" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-600 dark:focus:ring-offset-slate-900 cursor-pointer">
                    সব নির্বাচন করুন
                </label>
                <span class="text-xs font-semibold text-slate-400">{{ count($visibleChannels) }}টি</span>
            </div>

            <!-- SKELETON LOADER (Shows while loadGroup is running) -->
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

            <!-- ACTUAL CHANNEL LIST (Hides while loadGroup is running) -->
            <div wire:loading.remove wire:target="loadGroup" class="flex flex-col gap-2 p-3">
                @foreach ($visibleChannels as $channel)
                    @php
                        // Determine styles based on channel status
                        $isDead = $channel['status'] === 'DEAD';
                        $isBlocked = $channel['status'] === 'BLOCKED';

                        $containerClass = 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-900/10';
                        if ($isDead) $containerClass = 'border-rose-200 bg-rose-50/50 dark:border-rose-900/40 dark:bg-rose-900/10';
                        elseif ($isBlocked) $containerClass = 'border-orange-200 bg-orange-50/40 dark:border-orange-900/40 dark:bg-orange-900/10';
                    @endphp

                    <div wire:key="channel-{{ $channel['id'] }}" class="flex items-center gap-3 rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-sm {{ $containerClass }}">

                        <!-- Checkbox -->
                        <input type="checkbox" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-600 dark:focus:ring-offset-slate-900 cursor-pointer shrink-0">

                        <!-- Logo -->
                        <div class="grid size-12 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white p-1 shadow-sm overflow-hidden dark:border-slate-700 dark:bg-slate-800">
                            @if(str_starts_with($channel['logo'], 'http'))
                                <img src="{{ $channel['logo'] }}" alt="Logo" class="size-full object-contain p-0.5">
                            @else
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ $channel['logo'] }}</span>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ $channel['name'] }}</h3>
                                <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">{{ $channel['category'] }}</span>

                                <!-- HTTP Mixed Content Badge for Blocked -->
                                @if($channel['is_http'])
                                    <span class="rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-800 border border-orange-200/50 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-800/50">HTTP (mixed)</span>
                                @endif
                                @if($channel['proxy_needed'])
                                    <span class="rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-800 border border-orange-200/50 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-800/50">proxy লাগবে</span>
                                @endif
                            </div>

                            <p class="truncate text-[11px] font-medium text-slate-500 mt-0.5 dark:text-slate-400" title="{{ $channel['url'] }}">{{ $channel['url'] }}</p>
                            <!-- Mock Time -->
                            <p class="text-[10px] font-medium text-slate-400 mt-0.5 dark:text-slate-500">৬:৫৪:২৫ AM</p>
                        </div>

                        <!-- Actions (Right Side) based on Status -->
                        <div class="flex items-center gap-2 shrink-0">
                            @if($isDead)
                                <span class="flex items-center gap-1.5 rounded-full bg-rose-100/80 px-2.5 py-1 cursor-pointer text-[11px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/50">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    DEAD
                                </span>
                                <button wire:click="verifyChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Retry
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-500 shadow-sm hover:bg-rose-50 transition dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    ডিলিট
                                </button>
                            @elseif($isBlocked)
                                <span class="flex items-center gap-1.5 rounded-full bg-orange-100/80 px-2.5 py-1 cursor-pointer text-[11px] font-bold text-orange-700 dark:bg-orange-900/40 dark:text-orange-400 border border-orange-200/50 dark:border-orange-800/50">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    BLOCKED
                                </span>
                                <button wire:click="playChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play
                                </button>
                                <button onclick="copyToClipboard('{{ $channel['url'] }}')" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg> Copy
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="grid size-[30px] place-items-center rounded-lg border border-rose-200 bg-white text-rose-500 shadow-sm hover:bg-rose-50 transition dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @else
                                <span class="flex items-center gap-1.5 rounded-full bg-emerald-100/80 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> চলবে
                                </span>
                                <button wire:click="playChannel({{ $channel['id'] }})" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-400">
                                    <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play
                                </button>
                                <button onclick="copyToClipboard('{{ $channel['url'] }}')" class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path></svg> Copy
                                </button>
                                <button wire:click="deleteChannel({{ $channel['id'] }})" class="grid size-[30px] place-items-center cursor-pointer rounded-lg border border-rose-200 bg-white text-rose-500 shadow-sm hover:bg-rose-50 hover:text-rose-600 transition dark:border-rose-900/50 dark:bg-slate-800 dark:hover:bg-rose-900/30">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if(count($visibleChannels) === 0)
                    <div class="py-10 text-center text-slate-500 dark:text-slate-400">
                        <svg class="mx-auto size-10 opacity-30 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <p class="text-sm font-semibold">কোনো চ্যানেল পাওয়া যায়নি</p>
                    </div>
                @endif
            </div>
        </section>

        <footer class="pb-8 text-center text-xs font-bold text-slate-500">
            <p class="uppercase tracking-[0.3em] text-slate-400">Developed by</p>
            <p class="mt-1 text-slate-700 dark:text-slate-200">Monirujjaman Monjil</p>
            <div class="mx-auto mt-3 grid size-14 place-items-center rounded-full bg-gradient-to-br from-violet-600 to-sky-500 text-lg text-white shadow-lg shadow-violet-300">👨‍💻</div>
        </footer>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 left-1/2 z-50 flex -translate-x-1/2 flex-col gap-2 pointer-events-none"></div>

    <!-- Global JS for Copy & Toast -->
    <script>
        window.showToast = function(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if(!toastContainer) return;

            const toast = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-emerald-500/95' : 'bg-rose-500/95';
            toast.className = `flex items-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-full shadow-[0_8px_30px_rgb(0,0,0,0.12)] transition-all duration-300 transform translate-y-10 opacity-0 ${bgClass} backdrop-blur-md`;

            toast.innerHTML = type === 'success'
                ? `<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> <span>${message}</span>`
                : `<span>${message}</span>`;

            toastContainer.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);

            // Animate out
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        window.copyToClipboard = function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    window.showToast('Link Copied Successfully!');
                }).catch(err => {
                    window.showToast('Failed to copy link!', 'error');
                });
            } else {
                // Fallback for HTTP / non-secure contexts / Local network
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    window.showToast('Link Copied Successfully!');
                } catch (err) {
                    window.showToast('Failed to copy link!', 'error');
                }
                textArea.remove();
            }
        };
    </script>

    @script
    <script>
        const video = document.getElementById('live-tv-player');
        const videoContainer = document.getElementById('video-container');
        const status = document.getElementById('player-status');

        // Custom Controls
        const customControls = document.getElementById('custom-controls');
        const playPauseBtn = document.getElementById('play-pause-btn');
        const muteBtn = document.getElementById('mute-btn');
        const volumeSlider = document.getElementById('volume-slider');
        const pipBtn = document.getElementById('pip-btn');
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const topFullscreenBtn = document.getElementById('top-fullscreen-btn');
        const topStopBtn = document.getElementById('top-stop-btn');

        // SVG Icons
        const playIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>`;
        const pauseIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;
        const volHighIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>`;
        const volMuteIcon = `<svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>`;

        let shakaPlayer;
        let hlsPlayer;
        let hlsLoader;
        let shakaLoader;

        // --- IDLE TIMER LOGIC ---
        let idleTimer;
        let lastMouseX = 0;
        let lastMouseY = 0;

        function showControls() {
            videoContainer.style.cursor = 'default';
            customControls.style.opacity = '1';
            customControls.style.pointerEvents = 'auto';

            clearTimeout(idleTimer);

            if (!video.paused) {
                idleTimer = setTimeout(hideControls, 2500);
            }
        }

        function hideControls() {
            if (!video.paused) {
                videoContainer.style.cursor = 'none';
                customControls.style.opacity = '0';
                customControls.style.pointerEvents = 'none';
            }
        }

        videoContainer.addEventListener('mousemove', (e) => {
            if (e.clientX === lastMouseX && e.clientY === lastMouseY) return;
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
            showControls();
        });

        videoContainer.addEventListener('mousedown', showControls);
        videoContainer.addEventListener('touchstart', showControls);
        videoContainer.addEventListener('mouseleave', hideControls);

        customControls.addEventListener('mouseenter', () => clearTimeout(idleTimer));
        customControls.addEventListener('mouseleave', showControls);

        video.addEventListener('play', showControls);
        video.addEventListener('pause', showControls);

        // --- Basic UI Logic ---
        let statusTimeout;
        function updateStatus(message) {
            if (status) {
                status.textContent = message;
                status.style.opacity = '1';
                clearTimeout(statusTimeout);
                statusTimeout = setTimeout(() => {
                    status.style.opacity = '0';
                }, 3000);
            }
        }

        playPauseBtn.addEventListener('click', () => {
            if (video.paused) video.play();
            else video.pause();
        });

        video.addEventListener('play', () => playPauseBtn.innerHTML = pauseIcon);
        video.addEventListener('pause', () => playPauseBtn.innerHTML = playIcon);

        topStopBtn.addEventListener('click', () => {
            video.pause();
            updateStatus('Stopped');
            showControls();
        });

        muteBtn.addEventListener('click', () => {
            video.muted = !video.muted;
            if(!video.muted && video.volume === 0) video.volume = 0.5;
            volumeSlider.value = video.muted ? 0 : video.volume;
        });

        volumeSlider.addEventListener('input', (e) => {
            video.volume = e.target.value;
            video.muted = e.target.value === '0';
        });

        video.addEventListener('volumechange', () => {
            if (video.muted || video.volume === 0) {
                muteBtn.innerHTML = volMuteIcon;
                volumeSlider.value = 0;
            } else {
                muteBtn.innerHTML = volHighIcon;
                volumeSlider.value = video.volume;
            }
        });

        if (!document.pictureInPictureEnabled) pipBtn.style.display = 'none';
        pipBtn.addEventListener('click', async () => {
            try {
                if (document.pictureInPictureElement) await document.exitPictureInPicture();
                else await video.requestPictureInPicture();
            } catch (err) { console.error(err); }
        });

        const toggleFullscreen = async () => {
            if (document.fullscreenElement || document.webkitFullscreenElement) {
                if (document.exitFullscreen) await document.exitFullscreen();
                else if (document.webkitExitFullscreen) await document.webkitExitFullscreen();
            } else {
                if (videoContainer.requestFullscreen) await videoContainer.requestFullscreen();
                else if (videoContainer.webkitRequestFullscreen) await videoContainer.webkitRequestFullscreen();
            }
        };
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        topFullscreenBtn.addEventListener('click', toggleFullscreen);

        video.addEventListener('click', (e) => {
            if(e.target !== customControls && !customControls.contains(e.target)) {
                if (video.paused) video.play();
                else video.pause();
            }
        });

        // --- Player Core Logic ---
        function isHlsStream(url) { return /\.m3u8?(\?|#|$)/i.test(url) || /m3u8/i.test(url); }
        function isDashStream(url) { return /\.mpd(\?|#|$)/i.test(url); }

        function loadScript(src, globalName) {
            if (window[globalName]) return Promise.resolve();
            const existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) {
                return new Promise((resolve, reject) => {
                    existingScript.addEventListener('load', () => resolve(), { once: true });
                    existingScript.addEventListener('error', () => reject(new Error(`Unable to load ${src}`)), { once: true });
                });
            }
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = () => resolve();
                script.onerror = () => reject(new Error(`Unable to load ${src}`));
                document.head.appendChild(script);
            });
        }

        function resetPlayers() {
            if (hlsPlayer) { hlsPlayer.destroy(); hlsPlayer = null; }
            if (shakaPlayer) { shakaPlayer.unload().catch(() => {}); }
            if (video) { video.removeAttribute('src'); video.load(); }
        }

        async function playNative(url, name) {
            resetPlayers();
            video.src = url;
            updateStatus(`Loading ${name}...`);
            await video.play().catch(() => updateStatus('Tap play to start'));
        }

        async function playWithHlsJs(url, name) {
            hlsLoader ??= loadScript('https://cdn.jsdelivr.net/npm/hls.js@1.6.14/dist/hls.min.js', 'Hls');
            await hlsLoader;

            if (!window.Hls || !Hls.isSupported()) {
                await playNative(url, name);
                return;
            }

            resetPlayers();
            hlsPlayer = new Hls({ enableWorker: true, lowLatencyMode: true });

            hlsPlayer.on(Hls.Events.ERROR, (event, data) => {
                if (!data.fatal) return;
                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                    updateStatus(`Network error; retrying...`);
                    hlsPlayer.startLoad();
                    return;
                }
                if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                    updateStatus(`Media error; recovering...`);
                    hlsPlayer.recoverMediaError();
                    return;
                }
                updateStatus(`Unable to load in browser`);
                hlsPlayer.destroy();
                hlsPlayer = null;
                playNative(url, name).catch(() => updateStatus(`Error: ${data.details ?? 'unknown'}`));
            });

            hlsPlayer.on(Hls.Events.MANIFEST_PARSED, async () => {
                updateStatus(`Playing: ${name}`);
                await video.play().catch(() => updateStatus('Tap play to start'));
            });

            updateStatus(`Loading ${name}...`);
            hlsPlayer.loadSource(url);
            hlsPlayer.attachMedia(video);
        }

        async function playWithShaka(url, name) {
            shakaLoader ??= loadScript('https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.15.9/shaka-player.compiled.min.js', 'shaka');
            await shakaLoader;

            resetPlayers();
            shaka.polyfill.installAll();
            shakaPlayer = new shaka.Player(video);
            shakaPlayer.addEventListener('error', (event) => updateStatus(`Playback error: ${event.detail.code}`));

            updateStatus(`Loading ${name}...`);
            await shakaPlayer.load(url);
            updateStatus(`Playing: ${name}`);
            await video.play().catch(() => updateStatus('Tap play to start'));
        }

        async function loadStream(url, name = 'Live stream') {
            if (!video || !url) return;
            volumeSlider.value = video.muted ? 0 : video.volume;

            try {
                if (isHlsStream(url)) { await playWithHlsJs(url, name); return; }
                if (isDashStream(url)) { await playWithShaka(url, name); return; }
                await playNative(url, name);
            } catch (error) {
                if (isHlsStream(url)) {
                    updateStatus(`Native fallback...`);
                    await playNative(url, name).catch(() => updateStatus(`Error: ${error.code ?? error.message ?? 'unknown'}`));
                    return;
                }
                updateStatus(`Error: ${error.code ?? error.message ?? 'unknown'}`);
            }
        }

        loadStream(@js($this->selectedChannel()['url']), @js($this->selectedChannel()['name']));
        $wire.on('stream-selected', ({ url, name }) => loadStream(url, name));
    </script>
    @endscript
</div>
