<?php

use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake([
        'raw.githubusercontent.com/Monjil404/livetv/refs/heads/main/pro' => Http::response(<<<'M3U'
#EXTM3U
#EXTINF:-1 group-title="News",Jamuna TV
https://example.com/jamuna.m3u8
#EXTINF:-1 group-title="Sports",T Sports
https://example.com/tsports.m3u8
M3U),
    ]);
});

test('home page renders the live tv interface with public source groups', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('LiveTVPro')
        ->assertSee('Single stream URL')
        ->assertSee('পাবলিক সোর্স')
        ->assertSee('TechEasyLife 👑')
        ->assertSee('Mrgify BDIX ⭐')
        ->assertSee('Axsport')
        ->assertSee('Jamuna TV')
        ->assertSee('T Sports')
        ->assertSee('2 channels loaded from TechEasyLife 👑')
        ->assertSee('Premium Player')
        ->assertSee('Shaka Player ready')
        ->assertSee('Now Playing')
        ->assertSee('Monirujjaman Monjil')
        ->assertSee('hls.min.js', false);
});
