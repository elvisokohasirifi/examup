<?php

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Exams that feel')
        ->assertSee('Admin login')
        ->assertSee('Create. Deliver. Learn.');
});
