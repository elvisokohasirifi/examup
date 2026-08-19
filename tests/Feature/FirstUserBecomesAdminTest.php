<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the first user becomes an admin automatically', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    expect($firstUser->fresh()->role)->toBe(User::ROLE_ADMIN)
        ->and($secondUser->fresh()->role)->toBe(User::ROLE_EXAMINER);
});
