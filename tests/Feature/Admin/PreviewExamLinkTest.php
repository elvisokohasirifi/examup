<?php

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('admin can generate a preview link for an exam from the admin panel', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create([
        'show_index_number_field' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.exams.preview', $exam));

    $previewLink = ExamAccessLink::query()->where('exam_id', $exam->id)->latest('id')->first();

    expect($previewLink)->not->toBeNull()
        ->and($previewLink->created_by)->toBe($admin->id)
        ->and($previewLink->email)->toBeNull()
        ->and($previewLink->max_attempts)->toBe(0)
        ->and($previewLink->isPreview())->toBeTrue();

    $response->assertRedirect($previewLink->examUrl());

    $this->get($previewLink->examUrl())
        ->assertOk()
        ->assertSee('Index number');
});
