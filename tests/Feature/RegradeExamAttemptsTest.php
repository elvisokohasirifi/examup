<?php

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('an exam owner can regrade completed attempts using the updated answer key', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
    ]);
    $question = Question::factory()->create([
        'exam_id' => $exam->id,
        'points' => 2,
    ]);
    $selectedOption = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'position' => 1,
        'label' => 'Selected answer',
        'is_correct' => false,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'position' => 2,
        'label' => 'Previously correct answer',
        'is_correct' => true,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'score' => 0,
        'max_score' => 2,
        'score_percentage' => 0,
        'submitted_at' => now(),
    ]);
    $answer = ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'selected_option_ids' => [$selectedOption->id],
        'is_correct' => false,
        'score' => 0,
    ]);

    $selectedOption->update(['is_correct' => true]);
    $question->options()->whereKeyNot($selectedOption->id)->update(['is_correct' => false]);

    $this->actingAs($admin)
        ->get(route('admin.exams.results', $exam))
        ->assertOk()
        ->assertSee('Regrade completed attempts');

    $this->actingAs($admin)
        ->post(route('admin.exams.regrade', $exam))
        ->assertRedirect(route('admin.exams.results', $exam))
        ->assertSessionHas('success', 'Regraded 1 completed attempt(s).');

    expect($attempt->refresh())
        ->score->toBe('2.00')
        ->max_score->toBe('2.00')
        ->score_percentage->toBe('100.00')
        ->and($answer->refresh())
        ->is_correct->toBeTrue()
        ->score->toBe('2.00');
});

test('an examiner cannot regrade an exam they do not own', function () {
    $owner = User::factory()->examiner()->create();
    $otherExaminer = User::factory()->examiner()->create();
    $exam = Exam::factory()->for($owner, 'creator')->create();

    $this->actingAs($otherExaminer)
        ->post(route('admin.exams.regrade', $exam))
        ->assertForbidden();
});
