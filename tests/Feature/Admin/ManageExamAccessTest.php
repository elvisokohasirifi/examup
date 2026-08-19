<?php

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('admin can generate a shareable link from the exam access page', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.exams.access.store', $exam), [
            'action' => 'shareable_link',
        ]);

    $response->assertRedirect(route('admin.exams.access', $exam));

    $this->assertDatabaseHas('exam_access_links', [
        'exam_id' => $exam->id,
        'email' => null,
        'created_by' => $admin->id,
    ]);
});

test('admin can email unique links from the exam access page', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.exams.access.store', $exam), [
            'action' => 'email_list',
            'emails' => "first@example.com\r\nsecond@example.com",
        ]);

    $response->assertRedirect(route('admin.exams.access', $exam));

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
});
