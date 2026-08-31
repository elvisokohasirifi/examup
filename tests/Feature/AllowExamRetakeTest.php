<?php

use App\Actions\Exams\AllowExamRetakeAction;
use App\Actions\Exams\BuildExamStatisticsAction;
use App\Actions\Exams\SubmitExamAttemptAction;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ExamAccessLinkNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('an exam owner can email a student a single-use retake link', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
        'email' => 'student@example.com',
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'student_name' => 'Student One',
        'student_email' => 'student@example.com',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exams.results', $exam))
        ->assertOk()
        ->assertSee('Allow retake');

    $this->actingAs($admin)
        ->post(route('admin.exams.attempts.retake', [$exam, $attempt]))
        ->assertRedirect(route('admin.exams.results', $exam))
        ->assertSessionHas('success', 'A retake link was emailed to student@example.com.');

    $retakeLink = ExamAccessLink::query()
        ->where('meta->retake_for_attempt_id', $attempt->id)
        ->sole();

    expect($attempt->refresh()->superseded_at)
        ->toBeNull()
        ->and($retakeLink->email)->toBe('student@example.com')
        ->and($retakeLink->max_attempts)->toBe(1)
        ->and($retakeLink->meta['is_retake'])->toBeTrue();

    Notification::assertSentOnDemand(ExamAccessLinkNotification::class);
});

test('a submitted retake supersedes the old attempt in results and statistics', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    Question::factory()->create([
        'exam_id' => $exam->id,
        'points' => 2,
    ]);
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
        'email' => 'student@example.com',
    ]);
    $oldAttempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'student_name' => 'Old result',
        'student_email' => 'student@example.com',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'score' => 8,
        'max_score' => 10,
        'submitted_at' => now()->subHour(),
    ]);

    $retakeLink = app(AllowExamRetakeAction::class)->handle($oldAttempt, $admin);
    $retakeAttempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $retakeLink->id,
        'student_name' => 'New result',
        'student_email' => 'student@example.com',
        'status' => ExamAttempt::STATUS_IN_PROGRESS,
        'started_at' => now(),
    ]);

    app(SubmitExamAttemptAction::class)->handle($retakeAttempt);

    expect($oldAttempt->refresh())
        ->isSuperseded()->toBeTrue()
        ->and($oldAttempt->superseded_by_attempt_id)->toBe($retakeAttempt->id);

    $stats = app(BuildExamStatisticsAction::class)->handle($exam->fresh());

    expect($stats['attempt_count'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('admin.exams.results', $exam))
        ->assertOk()
        ->assertDontSee('Old result')
        ->assertSee('New result');
});

test('an examiner cannot allow a retake for another examiners exam', function () {
    $owner = User::factory()->examiner()->create();
    $otherExaminer = User::factory()->examiner()->create();
    $exam = Exam::factory()->for($owner, 'creator')->create();
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $owner->id,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'status' => ExamAttempt::STATUS_SUBMITTED,
    ]);

    $this->actingAs($otherExaminer)
        ->post(route('admin.exams.attempts.retake', [$exam, $attempt]))
        ->assertForbidden();
});
