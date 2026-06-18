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
     * @var list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}>
     */
    public array $channels = [];

    public function mount(): void
    {
        $this->loadGroup($this->activeGroupKey);
    }

    /**
     * @return list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}>
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

    /**
     * @return array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}
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
     * @return list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}>
     */
    private function parsePlaylist(string $playlist, string $fallbackCategory): array
    {
        $channels = [];
        $pendingName = null;
        $pendingCategory = $fallbackCategory;
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

                continue;
            }

            if (str_starts_with($line, '#') || ! preg_match('/^https?:\/\//i', $line)) {
                continue;
            }

            $name = $pendingName ?? basename(parse_url($line, PHP_URL_PATH) ?: 'Live Channel');
            $channels[] = [
                'id' => count($channels) + 1,
                'name' => $name,
                'category' => $pendingCategory,
                'url' => $line,
                'logo' => str($name)->substr(0, 2)->upper()->toString(),
                'status' => 'LIVE',
                'protocol' => str_contains(strtolower($line), '.mpd') ? 'DASH' : 'HLS',
                'response' => 'Loaded',
                'viewers' => 0,
            ];
            $pendingName = null;
            $pendingCategory = $fallbackCategory;
        }

        return $channels;
    }
};
?>
<div class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,#d8dcff_0,#f9f7ff_28%,#f2ecff_48%,#eafbf8_100%)] text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-5 px-4 py-6 sm:py-8">
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
                <button wire:click="playSingleStream" class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-violet-300 transition hover:bg-violet-700">▶ Play</button>
                <button type="button" class="rounded-xl border border-violet-100 bg-white px-4 py-3 text-xs font-bold text-violet-600 shadow-sm dark:border-slate-700 dark:bg-slate-800">ⓘ Check</button>
            </div>
            <label class="mt-4 block text-xs font-bold uppercase tracking-wider text-violet-600">Playlist / M3U / HTML embed</label>
            <textarea wire:model="playlist" rows="4" class="mt-2 w-full rounded-xl border border-violet-100 bg-violet-50/40 p-3 text-xs outline-none ring-violet-300 transition focus:ring-2 dark:border-slate-700 dark:bg-slate-800"></textarea>
            <div class="mt-3 flex gap-2">
                <button wire:click="loadGroup('{{ $activeGroupKey }}')" class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-bold text-white">Reload Group</button>
                <button wire:click="clearInputs" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-500 dark:bg-slate-800">Clear</button>
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

        <section class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-violet-200/50 ring-1 ring-white/80 dark:bg-slate-900 dark:ring-slate-700">
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <h2 class="text-sm font-extrabold">{{ $this->selectedChannel()['name'] }}</h2>
                    <p class="text-xs font-semibold text-emerald-500">● LIVE PLAYER · {{ $this->selectedChannel()['protocol'] }}</p>
                </div>
                <div class="flex gap-2 text-xs font-bold">
                    <button class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">Full</button>
                    <button class="rounded-full bg-rose-50 px-3 py-1 text-rose-500 dark:bg-rose-950/40">Stop</button>
                </div>
            </div>
            <div wire:ignore class="relative aspect-video bg-slate-950">
                <video id="live-tv-player" class="video-js vjs-default-skin vjs-big-play-centered size-full" controls autoplay muted playsinline preload="auto" poster="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1400&q=80"></video>
                <div id="player-status" class="pointer-events-none absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white">Video.js ready</div>
            </div>
            <div class="grid grid-cols-4 divide-x divide-violet-100 border-t border-violet-100 text-center text-xs dark:divide-slate-700 dark:border-slate-700">
                <div class="p-3"><strong class="block text-violet-600">{{ count($channels) }}</strong><span class="text-slate-400">Total</span></div>
                <div class="p-3"><strong class="block text-emerald-500">{{ count($channels) }}</strong><span class="text-slate-400">Live</span></div>
                <div class="p-3"><strong class="block text-orange-500">0</strong><span class="text-slate-400">Down</span></div>
                <div class="p-3"><strong class="block text-rose-500">0</strong><span class="text-slate-400">Errors</span></div>
            </div>
        </section>

        <section class="rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700">
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-2 text-xs font-bold">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-600">All</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-600">Live</span>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-600">Offline</span>
                </div>
                <input wire:model.live="search" class="rounded-xl border border-violet-100 bg-white px-3 py-2 text-xs outline-none ring-violet-300 transition focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="Search channel name, category, URL...">
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($this->filteredChannels() as $channel)
                    <article wire:key="channel-{{ $channel['id'] }}" class="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800/70">
                        <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-sky-400 text-xs font-black text-white">{{ $channel['logo'] }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-sm font-extrabold">{{ $channel['name'] }}</h3>
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase text-blue-600 dark:bg-blue-950/50">{{ $channel['category'] }}</span>
                            </div>
                            <p class="truncate text-[11px] text-slate-400">{{ $channel['url'] }}</p>
                        </div>
                        <div class="hidden text-right text-[11px] font-semibold text-slate-400 sm:block">{{ number_format($channel['viewers']) }} viewers</div>
                        <button wire:click="playChannel({{ $channel['id'] }})" class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-bold text-white">▶ Play</button>
                        <button class="rounded-full bg-white px-3 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">Copy</button>
                    </article>
                @endforeach
            </div>
        </section>

        <footer class="pb-8 text-center text-xs font-bold text-slate-500">
            <p class="uppercase tracking-[0.3em] text-slate-400">Developed by</p>
            <p class="mt-1 text-slate-700 dark:text-slate-200">Monirujjaman Monjil</p>
            <div class="mx-auto mt-3 grid size-14 place-items-center rounded-full bg-gradient-to-br from-violet-600 to-sky-500 text-lg text-white shadow-lg shadow-violet-300">👨‍💻</div>
        </footer>
    </div>

    @script
    <script>
        const video = document.getElementById('live-tv-player');
        const status = document.getElementById('player-status');
        let videoPlayer;
        let videoJsLoader;
        let currentStreamName = 'Live stream';

        function updateStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function streamType(url) {
            if (/\.m3u8?(\?|#|$)/i.test(url) || /m3u8/i.test(url)) {
                return 'application/x-mpegURL';
            }

            if (/\.mpd(\?|#|$)/i.test(url)) {
                return 'application/dash+xml';
            }

            if (/\.mp4(\?|#|$)/i.test(url)) {
                return 'video/mp4';
            }

            return 'application/x-mpegURL';
        }

        function loadStylesheet(href) {
            if (document.querySelector(`link[href="${href}"]`)) {
                return;
            }

            const link = document.createElement('link');
            link.href = href;
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        }

        function loadScript(src, globalName) {
            if (window[globalName]) {
                return Promise.resolve();
            }

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

        async function ensureVideoJs() {
            loadStylesheet('https://vjs.zencdn.net/8.23.8/video-js.min.css');
            videoJsLoader ??= loadScript('https://vjs.zencdn.net/8.23.8/video.min.js', 'videojs');
            await videoJsLoader;
        }

        async function nativeFallback(url, name, error) {
            if (!video) {
                return;
            }

            video.src = url;
            updateStatus(`Video.js failed; trying native playback for ${name}`);
            await video.play().catch(() => updateStatus(`Unable to load stream: ${error?.message ?? 'browser blocked playback'}`));
        }

        async function loadStream(url, name = 'Live stream') {
            if (!video || !url) {
                return;
            }

            try {
                currentStreamName = name;
                await ensureVideoJs();

                if (!videoPlayer) {
                    videoPlayer = videojs(video, {
                        autoplay: 'muted',
                        controls: true,
                        fluid: true,
                        liveui: true,
                        preload: 'auto',
                        responsive: true,
                        html5: {
                            vhs: {
                                overrideNative: true,
                                enableLowInitialPlaylist: true,
                            },
                        },
                    });

                    videoPlayer.on('error', () => {
                        const error = videoPlayer.error();
                        updateStatus(`Unable to load stream: ${error?.code ?? 'unknown'}`);
                    });

                    videoPlayer.on('playing', () => updateStatus(`Playing ${currentStreamName}`));
                    videoPlayer.on('waiting', () => updateStatus(`Buffering ${currentStreamName}`));
                }

                updateStatus(`Loading ${name} with Video.js`);
                videoPlayer.src({
                    src: url,
                    type: streamType(url),
                });
                videoPlayer.load();
                await videoPlayer.play().catch(() => updateStatus('Tap play to start'));
            } catch (error) {
                await nativeFallback(url, name, error);
            }
        }

        loadStream(@js($this->selectedChannel()['url']), @js($this->selectedChannel()['name']));

        $wire.on('stream-selected', ({ url, name }) => loadStream(url, name));
    </script>
    @endscript
</div>
