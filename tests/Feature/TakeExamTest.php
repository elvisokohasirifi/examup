<?php

use App\Actions\Exams\StartExamAttemptAction;
use App\Actions\Exams\SubmitExamAttemptAction;
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

test('student can start and submit an exam from a secure link', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'all',
        'show_score_to_student' => true,
        'show_index_number_field' => true,
    ]);

    $question = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
        'points' => 1,
    ]);

    $option = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'position' => 1,
        'label' => 'Correct option',
        'is_correct' => true,
    ]);

    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->set('candidate.student_index_number', 'IDX-001')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->set("responses.{$question->id}.selected_option_id", $option->id)
        ->call('submitExam', app(SubmitExamAttemptAction::class))
        ->assertSee('Exam submitted')
        ->assertSee('1.00 / 1.00');
});

test('index number is required only when the exam is configured to ask for it', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => true,
    ]);

    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertHasErrors(['candidate.student_index_number' => 'required']);
});

test('shuffled exams persist a question order for each attempt', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'shuffle_questions' => true,
    ]);

    $questions = Question::factory()->count(3)->sequence(
        ['exam_id' => $exam->id, 'position' => 1],
        ['exam_id' => $exam->id, 'position' => 2],
        ['exam_id' => $exam->id, 'position' => 3],
    )->create();

    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    $component = Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class));

    $attempt = ExamAttempt::query()->findOrFail($component->get('attempt.id'));

    expect($attempt->meta['question_order'] ?? [])
        ->toHaveCount(3)
        ->and(collect($attempt->meta['question_order'])->sort()->values()->all())
        ->toBe($questions->pluck('id')->sort()->values()->all());
});

test('expired exams cannot be started even when the access link is still active', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'expires_at' => now()->subMinute(),
    ]);

    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
        'expires_at' => now()->addDay(),
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])->assertForbidden();
});
