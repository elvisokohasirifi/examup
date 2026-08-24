<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('exam create page shows the question builder controls', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get('/admin/exam/create');

    $response->assertOk()
        ->assertSee('Exam Builder')
        ->assertSee('Continue to questions')
        ->assertSee('Import questions from a text file')
        ->assertSee('Download sample file')
        ->assertSee('Questions are loaded into the builder immediately')
        ->assertSee('data-question-import-input', false)
        ->assertSee('data-question-import-feedback', false)
        ->assertSee('Add another question')
        ->assertSee('Accepted answers (one per line)');
});
