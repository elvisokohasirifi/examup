<?php

use App\Models\Exam;
use App\Models\ExamAccessLink;
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
    $response->assertSessionHas('shareable_link_url');

    $this->assertDatabaseHas('exam_access_links', [
        'exam_id' => $exam->id,
        'email' => null,
        'created_by' => $admin->id,
    ]);

    expect($exam->accessLinks()->sole()->expires_at?->toDateTimeString())
        ->toBe($exam->expires_at?->toDateTimeString());
});

test('shareable links stay synchronized with their exam expiry', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create([
        'expires_at' => now()->addDay(),
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
        'expires_at' => $exam->expires_at,
        'meta' => ['shareable' => true],
    ]);
    $newExpiry = now()->addWeek();

    $exam->update(['expires_at' => $newExpiry]);

    expect($link->refresh()->expires_at?->toDateTimeString())
        ->toBe($newExpiry->toDateTimeString());
});

test('admin can delete a shareable link from the exam access page', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
        'meta' => ['shareable' => true],
    ]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.exams.access.destroy', [$exam, $link]));

    $response
        ->assertRedirect(route('admin.exams.access', $exam))
        ->assertSessionHas('status', 'Shareable link deleted successfully.');

    $this->assertModelMissing($link);
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
