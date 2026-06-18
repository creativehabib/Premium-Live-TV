<?php

test('home page renders the live tv interface', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('LiveTVPro')
        ->assertSee('Single stream URL')
        ->assertSee('Shaka Player ready')
        ->assertSee('JamunaTV · News')
        ->assertSee('HLS/M3U8')
        ->assertSee('200 OK')
        ->assertSee('Monirujjaman Monjil')
        ->assertSee('shaka-player', false);
});
