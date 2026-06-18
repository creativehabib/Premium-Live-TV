<?php

test('home page renders the live tv interface', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('LiveTVPro')
        ->assertSee('Single stream URL')
        ->assertSee('Shaka Player ready')
        ->assertSee('Star News')
        ->assertSee('Monirujjaman Monjil')
        ->assertSee('shaka-player', false);
});
