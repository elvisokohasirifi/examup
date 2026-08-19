<?php

use App\Actions\Exams\StartExamAttemptAction;
use App\Actions\Exams\SubmitExamAttemptAction;
use App\Livewire\TakeExam;
use App\Models\Exam;
use App\Models\ExamAccessLink;
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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->set("responses.{$question->id}.selected_option_id", $option->id)
        ->call('submitExam', app(SubmitExamAttemptAction::class))
        ->assertSee('Exam submitted')
        ->assertSee('1.00 / 1.00');
});
