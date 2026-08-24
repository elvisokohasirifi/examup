<?php

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SuspiciousActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('an exam owner can review a completed students answers, grading, and activity', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create(['title' => 'Science assessment']);
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
    ]);
    $question = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
        'prompt' => 'Which gas do plants absorb?',
        'points' => 2,
    ]);
    $correctOption = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'label' => 'Carbon dioxide',
        'value' => 'Carbon dioxide',
        'is_correct' => true,
    ]);
    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'label' => 'Oxygen',
        'value' => 'Oxygen',
        'is_correct' => false,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'student_name' => 'Amina Mensah',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'started_at' => now()->subMinutes(10),
        'submitted_at' => now(),
        'duration_seconds' => 600,
        'score' => 2,
        'max_score' => 2,
        'score_percentage' => 100,
        'ip_address' => '127.0.0.1',
    ]);

    ExamAnswer::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'selected_option_ids' => [$correctOption->id],
        'is_correct' => true,
        'score' => 2,
    ]);
    SuspiciousActivity::factory()->create([
        'exam_attempt_id' => $attempt->id,
        'event_type' => 'window_blur',
        'severity' => 'medium',
        'details' => 'The exam tab lost focus.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exams.attempts.show', [$exam, $attempt]))
        ->assertOk()
        ->assertSee("Amina Mensah's completed exam", false)
        ->assertSee('Which gas do plants absorb?')
        ->assertSee('Answer choices')
        ->assertSee('Carbon dioxide')
        ->assertSee('Oxygen')
        ->assertSee('Student selected')
        ->assertSee('Correct answer')
        ->assertSee('2 / 2 points')
        ->assertDontSee('2.00 / 2 points')
        ->assertSee('Suspicious events')
        ->assertSee('window blur')
        ->assertSee('The exam tab lost focus.');
});

test('an attempt cannot be reviewed through another exam', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    $otherExam = Exam::factory()->for($admin, 'creator')->create();
    $attempt = ExamAttempt::factory()->create(['exam_id' => $otherExam->id]);

    $this->actingAs($admin)
        ->get(route('admin.exams.attempts.show', [$exam, $attempt]))
        ->assertNotFound();
});

test('the results page links completed attempts to their individual review page', function () {
    $admin = User::factory()->admin()->create();
    $exam = Exam::factory()->for($admin, 'creator')->create();
    $accessLink = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $admin->id,
    ]);
    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $accessLink->id,
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'duration_seconds' => 583,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.exams.results', $exam))
        ->assertOk()
        ->assertSee('View completed exam')
        ->assertSee('Average completion time')
        ->assertSee('9.7')
        ->assertSee('minutes')
        ->assertSee(route('admin.exams.attempts.show', [$exam, $attempt]));
});
