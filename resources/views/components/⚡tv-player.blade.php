<?php

use Livewire\Component;

new class extends Component {
    public string $singleStreamUrl = 'https://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8';

    public string $playlist = "#EXTM3U\n#EXTINF:-1 tvg-logo=\"https://placehold.co/80x80/22c55e/ffffff?text=SN\" group-title=\"News\",Star News\nhttps://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8";

    public string $search = '';

    public string $activeCategory = 'All';

    public int $selectedChannelId = 1;

    /**
     * @var list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}>
     */
    public array $channels = [
        ['id' => 1, 'name' => 'Star News', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1710/output/index.m3u8', 'logo' => 'SN', 'status' => 'LIVE', 'protocol' => 'HLS', 'response' => '200 OK', 'viewers' => 1240],
        ['id' => 2, 'name' => 'DBC News HD', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1728/output/index.m3u8', 'logo' => 'DN', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 980],
        ['id' => 3, 'name' => 'Maasranga TV', 'category' => 'Entertainment', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1722/output/index.m3u8', 'logo' => 'MT', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 732],
        ['id' => 4, 'name' => 'Ekattor HD', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1705/output/index.m3u8', 'logo' => '71', 'status' => 'LIVE', 'protocol' => 'HLS', 'response' => '200 OK', 'viewers' => 654],
        ['id' => 5, 'name' => 'Channel 24 HD', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1703/output/index.m3u8', 'logo' => '24', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 610],
        ['id' => 6, 'name' => 'ATN News', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1706/output/index.m3u8', 'logo' => 'AT', 'status' => 'LIVE', 'protocol' => 'HLS', 'response' => '200 OK', 'viewers' => 588],
        ['id' => 7, 'name' => 'Jamuna TV', 'category' => 'News', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1701/output/index.m3u8', 'logo' => 'JT', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 550],
        ['id' => 8, 'name' => 'Deepto TV HD', 'category' => 'Entertainment', 'url' => 'https://owrcovcrpy.gpcdn.net/bpk-tv/1711/output/index.m3u8', 'logo' => 'DT', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 436],
        ['id' => 9, 'name' => 'Nexus 24 HD', 'category' => 'Movies', 'url' => 'https://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8', 'logo' => 'NX', 'status' => 'LIVE', 'protocol' => 'HLS', 'response' => '200 OK', 'viewers' => 384],
        ['id' => 10, 'name' => 'T Sports', 'category' => 'Sports', 'url' => 'https://storage.googleapis.com/shaka-demo-assets/angel-one/dash.mpd', 'logo' => 'TS', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 360],
        ['id' => 11, 'name' => 'Kids Stars', 'category' => 'Kids', 'url' => 'https://storage.googleapis.com/shaka-demo-assets/angel-one-hls/hls.m3u8', 'logo' => 'KS', 'status' => 'LIVE', 'protocol' => 'HLS', 'response' => '200 OK', 'viewers' => 315],
        ['id' => 12, 'name' => 'Music Beats', 'category' => 'Music', 'url' => 'https://storage.googleapis.com/shaka-demo-assets/angel-one/dash.mpd', 'logo' => 'MB', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 292],
        ['id' => 12, 'name' => 'Somoy TV', 'category' => 'Sports', 'url' => 'https://live.thebosstv.com:30443/dwlive/Somoy-TV/chunks.m3u8', 'logo' => 'FW', 'status' => 'LIVE', 'protocol' => 'DASH', 'response' => '200 OK', 'viewers' => 292],
    ];

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return ['All', 'News', 'Sports', 'Entertainment', 'Movies', 'Kids', 'Music'];
    }

    /**
     * @return list<array{id:int,name:string,category:string,url:string,logo:string,status:string,protocol:string,response:string,viewers:int}>
     */
    public function filteredChannels(): array
    {
        return array_values(array_filter($this->channels, function (array $channel): bool {
            $matchesCategory = $this->activeCategory === 'All' || $channel['category'] === $this->activeCategory;
            $matchesSearch = $this->search === '' || str_contains(strtolower($channel['name'].' '.$channel['category']), strtolower($this->search));

            return $matchesCategory && $matchesSearch;
        }));
    }

    public function selectCategory(string $category): void
    {
        $this->activeCategory = in_array($category, $this->categories(), true) ? $category : 'All';
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

    public function selectedChannel(): array
    {
        return collect($this->channels)->firstWhere('id', $this->selectedChannelId) ?? $this->channels[0];
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
                <span class="rounded-full bg-white/80 px-3 py-1 text-violet-600 shadow-sm">● Watchlist: {{ count($channels) }}</span>
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
                <button class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-bold text-white">Parse M3U</button>
                <button wire:click="clearInputs" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-500 dark:bg-slate-800">Clear</button>
            </div>
        </section>

        <section class="rounded-2xl bg-white/85 p-4 shadow-xl shadow-violet-200/50 ring-1 ring-white/80 backdrop-blur dark:bg-slate-900/80 dark:ring-slate-700">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs font-bold uppercase tracking-wider text-violet-600">
                <span>Channel groups</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-500 dark:bg-slate-800">{{ count($this->filteredChannels()) }} visible</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->categories() as $category)
                    <button wire:key="category-{{ $category }}" wire:click="selectCategory('{{ $category }}')" class="rounded-full border px-3 py-1.5 text-xs font-bold transition {{ $activeCategory === $category ? 'border-violet-500 bg-violet-600 text-white shadow-lg shadow-violet-200' : 'border-violet-100 bg-white text-slate-600 hover:border-violet-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">{{ $category }}</button>
                @endforeach
            </div>
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
                <video id="live-tv-player" class="size-full" controls autoplay muted playsinline poster="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1400&q=80"></video>
                <div id="player-status" class="pointer-events-none absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white">Shaka Player ready</div>
            </div>
            <div class="grid grid-cols-4 divide-x divide-violet-100 border-t border-violet-100 text-center text-xs dark:divide-slate-700 dark:border-slate-700">
                <div class="p-3"><strong class="block text-violet-600">44</strong><span class="text-slate-400">Total</span></div>
                <div class="p-3"><strong class="block text-emerald-500">44</strong><span class="text-slate-400">Live</span></div>
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
        let player;

        function updateStatus(message) {
            if (status) {
                status.textContent = message;
            }
        }

        async function loadStream(url, name = 'Live stream') {
            if (!window.shaka || !video || !url) {
                return;
            }

            if (!player) {
                shaka.polyfill.installAll();
                player = new shaka.Player(video);
                player.addEventListener('error', (event) => updateStatus(`Playback error: ${event.detail.code}`));
            }

            try {
                updateStatus(`Loading ${name}`);
                await player.load(url);
                updateStatus(`Playing ${name}`);
                await video.play().catch(() => updateStatus('Tap play to start'));
            } catch (error) {
                updateStatus(`Unable to load stream: ${error.code ?? 'unknown'}`);
            }
        }

        const bootstrapShaka = () => {
            if (window.shaka) {
                loadStream(@js($this->selectedChannel()['url']), @js($this->selectedChannel()['name']));
            }
        };

        if (!window.shaka) {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.15.9/shaka-player.compiled.min.js';
            script.onload = bootstrapShaka;
            document.head.appendChild(script);
        } else {
            bootstrapShaka();
        }

        $wire.on('stream-selected', ({ url, name }) => loadStream(url, name));
    </script>
    @endscript
</div>
