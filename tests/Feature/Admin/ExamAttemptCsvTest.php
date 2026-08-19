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

test('csv results include each students answer for every exam question', function () {
    $admin = User::factory()->admin()->create();

    $exam = Exam::factory()->for($admin, 'creator')->create([
        'title' => 'Physics Quiz',
    ]);

    $multipleChoiceQuestion = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
        'prompt' => 'Which planet is known as the red planet?',
    ]);

    $marsOption = QuestionOption::factory()->create([
        'question_id' => $multipleChoiceQuestion->id,
        'position' => 1,
        'label' => 'Mars',
        'value' => 'Mars',
        'is_correct' => true,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $multipleChoiceQuestion->id,
        'position' => 2,
        'label' => 'Venus',
        'value' => 'Venus',
        'is_correct' => false,
    ]);

    $fillInQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'prompt' => 'State the unit of electrical resistance.',
        'accepted_answers' => ['Ohm'],
    ]);

    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
    ]);

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'student_name' => 'Student One',
        'student_email' => 'student@example.com',
        'student_index_number' => 'IDX-100',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'score' => 5,
        'max_score' => 5,
        'score_percentage' => 100,
        'started_at' => now()->subMinutes(15),
        'submitted_at' => now()->subMinutes(5),
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $multipleChoiceQuestion->id,
        'selected_option_ids' => [$marsOption->id],
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $fillInQuestion->id,
        'answer_text' => 'Ohm',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.exams.csv', $exam));

    $response->assertOk();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Q1: Which planet is known as the red planet?')
        ->toContain('Q2: State the unit of electrical resistance.')
        ->toContain('Student One')
        ->toContain('Mars')
        ->toContain('Ohm');
});
