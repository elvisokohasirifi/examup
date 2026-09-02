<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('admins and examiners can access the help page', function (string $role) {
    $user = $role === 'admin'
        ? User::factory()->admin()->create()
        : User::factory()->examiner()->create();

    $this->actingAs($user)
        ->get(route('admin.help'))
        ->assertOk()
        ->assertSee('ExamUp guide')
        ->assertSee('Create an exam')
        ->assertSee('Bulk allow retakes');
})->with(['admin', 'examiner']);
