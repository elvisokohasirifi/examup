<?php

use App\Actions\Exams\AutoSubmitExpiredExamAttemptsAction;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('expired in-progress attempts are auto-submitted', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create(['created_by' => $examiner->id]);
    $question = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => true,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $link->id,
        'status' => ExamAttempt::STATUS_IN_PROGRESS,
        'started_at' => now()->subMinutes(30),
        'expires_at' => now()->subMinute(),
    ]);

    $submittedCount = app(AutoSubmitExpiredExamAttemptsAction::class)->handle();

    expect($submittedCount)->toBe(1)
        ->and($attempt->fresh()->status)->toBe(ExamAttempt::STATUS_AUTO_SUBMITTED)
        ->and($attempt->fresh()->submitted_at)->not->toBeNull();
});
