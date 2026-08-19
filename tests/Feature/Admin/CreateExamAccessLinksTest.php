<?php

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('admin can create multiple exam access links from a newline separated textbox', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();

    $response = $this
        ->actingAs($admin)
        ->post('/admin/exam-access-link', [
            'exam_id' => $exam->id,
            'emails' => "first@example.com\r\nsecond@example.com",
            'max_attempts' => 2,
            'send_email' => 1,
            'is_active' => 1,
            'expires_at' => now()->addDay()->toDateTimeString(),
        ]);

    $response->assertRedirect('/admin/exam-access-link');

    $this->assertDatabaseHas('exam_access_links', [
        'exam_id' => $exam->id,
        'email' => 'first@example.com',
        'created_by' => $admin->id,
    ]);

    $this->assertDatabaseHas('exam_access_links', [
        'exam_id' => $exam->id,
        'email' => 'second@example.com',
        'created_by' => $admin->id,
    ]);

    expect($exam->accessLinks()->count())->toBe(2);
});
