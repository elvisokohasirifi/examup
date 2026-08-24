<?php

use App\Actions\Exams\ResumeExamAttemptAction;
use App\Actions\Exams\SaveExamAnswerAction;
use App\Actions\Exams\StartExamAttemptAction;
use App\Livewire\TakeExam;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('a student resumes a shareable-link attempt from the same browser session', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'one_at_a_time',
        'show_index_number_field' => false,
    ]);
    $firstQuestion = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
    ]);
    $option = QuestionOption::factory()->create([
        'question_id' => $firstQuestion->id,
        'label' => 'Saved answer',
        'is_correct' => true,
    ]);
    $secondQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
        'email' => null,
        'max_attempts' => 0,
    ]);

    $startedAttempt = Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->set("responses.{$firstQuestion->id}.selected_option_id", $option->id)
        ->call('nextQuestion', app(SaveExamAnswerAction::class));

    $attemptId = $startedAttempt->get('attempt.id');

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSet('resumableAttempt.id', $attemptId)
        ->set('candidate.student_email', 'student@example.com')
        ->call('resumeAttempt')
        ->assertSet('attempt.id', $attemptId)
        ->assertSet('currentQuestionIndex', 1)
        ->assertSet("responses.{$firstQuestion->id}.selected_option_id", $option->id)
        ->assertSee($secondQuestion->prompt);

    expect(ExamAttempt::query()->where('exam_access_link_id', $link->id)->count())->toBe(1);
});

test('an individual email link resumes its active attempt without a browser session', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create(['created_by' => $examiner->id]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
        'email' => 'student@example.com',
        'max_attempts' => 1,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $link->id,
        'status' => ExamAttempt::STATUS_IN_PROGRESS,
        'student_email' => 'student@example.com',
        'meta' => ['question_order' => [], 'current_question_index' => 0],
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])->assertSet('attempt.id', $attempt->id);
});

test('an attempt in the recovery URL can resume after a session reset', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create(['created_by' => $examiner->id]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $link->id,
        'status' => ExamAttempt::STATUS_IN_PROGRESS,
    ]);

    $resumedAttempt = app(ResumeExamAttemptAction::class)->fromRecoveryUrl($link, $attempt->id);

    expect($resumedAttempt?->id)->toBe($attempt->id);
});
