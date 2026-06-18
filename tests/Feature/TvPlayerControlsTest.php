<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('hides native video progress and time controls', function () {
    Http::fake([
        '*' => Http::response("#EXTM3U\n#EXTINF:-1,Test Channel\nhttps://example.com/live.m3u8\n"),
    ]);

    Livewire::test('tv-player')
        ->assertSee('id="live-tv-player"', false)
        ->assertDontSee('id="live-tv-player" class="size-full" controls', false)
        ->assertSee('LIVE')
        ->assertSee('id="player-play-toggle"', false)
        ->assertSee('id="player-stop"', false);
});
