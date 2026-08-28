<?php

use App\Actions\Exams\SubmitExamAttemptAction;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it grades multiple choice and fill in answers', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'all',
    ]);

    $mcq = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
        'points' => 2,
    ]);

    $correctOption = QuestionOption::factory()->create([
        'question_id' => $mcq->id,
        'position' => 1,
        'label' => 'Correct',
        'is_correct' => true,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $mcq->id,
        'position' => 2,
        'label' => 'Wrong',
        'is_correct' => false,
    ]);

    $fillIn = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'points' => 3,
        'accepted_answers' => ['Laravel', 'The Laravel Framework'],
    ]);

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => ExamAccessLink::factory()->create([
            'exam_id' => $exam->id,
            'created_by' => $examiner->id,
        ])->id,
        'started_at' => now()->subMinutes(10),
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $mcq->id,
        'selected_option_ids' => [$correctOption->id],
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $fillIn->id,
        'answer_text' => 'laravel',
    ]);

    $gradedAttempt = app(SubmitExamAttemptAction::class)->handle($attempt);

    expect((float) $gradedAttempt->score)->toBe(5.0)
        ->and((float) $gradedAttempt->score_percentage)->toBe(100.0)
        ->and($gradedAttempt->status)->toBe(ExamAttempt::STATUS_SUBMITTED);
});

test('it ignores extra spacing and casing for fill in answers', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'all',
    ]);

    $fillIn = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 1,
        'points' => 2,
        'accepted_answers' => ['The Laravel Framework'],
    ]);

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => ExamAccessLink::factory()->create([
            'exam_id' => $exam->id,
            'created_by' => $examiner->id,
        ])->id,
        'started_at' => now()->subMinutes(5),
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $fillIn->id,
        'answer_text' => '  the   laravel framework  ',
    ]);

    $gradedAttempt = app(SubmitExamAttemptAction::class)->handle($attempt);

    expect((float) $gradedAttempt->score)->toBe(2.0)
        ->and((float) $gradedAttempt->score_percentage)->toBe(100.0)
        ->and($gradedAttempt->answers->first()->is_correct)->toBeTrue();
});

test('it grades question bank attempts using only the selected question points', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create(['created_by' => $examiner->id]);
    $selectedQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 1,
        'points' => 2,
        'accepted_answers' => ['Correct'],
    ]);
    $incorrectQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'points' => 3,
        'accepted_answers' => ['Correct'],
    ]);
    $unselectedQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 3,
        'points' => 5,
        'accepted_answers' => ['Correct'],
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => ExamAccessLink::factory()->create([
            'exam_id' => $exam->id,
            'created_by' => $examiner->id,
        ])->id,
        'meta' => ['question_order' => [$selectedQuestion->id, $incorrectQuestion->id]],
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $selectedQuestion->id,
        'answer_text' => 'Correct',
    ]);
    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $incorrectQuestion->id,
        'answer_text' => 'Wrong',
    ]);

    $gradedAttempt = app(SubmitExamAttemptAction::class)->handle($attempt);

    expect((float) $gradedAttempt->score)
        ->toBe(2.0)
        ->and((float) $gradedAttempt->max_score)
        ->toBe(5.0)
        ->and((float) $gradedAttempt->score_percentage)
        ->toBe(40.0)
        ->and($gradedAttempt->answers)
        ->toHaveCount(2)
        ->and($gradedAttempt->answers->pluck('question_id'))
        ->not->toContain($unselectedQuestion->id);
});
