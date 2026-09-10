<?php

use App\Actions\Exams\SaveExamAnswerAction;
use App\Actions\Exams\StartExamAttemptAction;
use App\Actions\Exams\SubmitExamAttemptAction;
use App\Livewire\TakeExam;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SuspiciousActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('student can start and submit an exam from a secure link', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'all',
        'description' => "First description line\nSecond description line",
        'instructions' => "First instruction line\nSecond instruction line",
        'show_score_to_student' => true,
        'show_correct_answers_to_student' => true,
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
        ->assertSee('whitespace-pre-line', false)
        ->assertSee('Network status')
        ->assertSee('Checking connection')
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->set('candidate.student_index_number', 'IDX-001')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertDispatched('exam-attempt-started')
        ->set("responses.{$question->id}.selected_option_id", $option->id)
        ->call('requestSubmission')
        ->assertSet('showSubmitConfirmation', true)
        ->assertSee('Your answers will be submitted and you will not be able to edit them afterwards.')
        ->call('submitExam', app(SubmitExamAttemptAction::class))
        ->assertSee('Exam submitted')
        ->assertSee('Your answer: Correct option')
        ->assertSee('1 / 1');
});

test('the public exam page loads Microsoft Clarity', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create(['created_by' => $examiner->id]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    $this->get($link->examUrl())
        ->assertOk()
        ->assertSee('https://www.clarity.ms/tag/', false)
        ->assertSee("'yg9r5l61hg'", false);
});

test('an active exam warns the student before browser back navigation', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
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
        ->call('warnBeforeLeaving')
        ->assertSet('showNavigationWarning', true)
        ->assertSee('Leave this exam?')
        ->assertSee('Stay on exam')
        ->assertSee('Leave exam')
        ->call('confirmNavigationAway')
        ->assertSet('showNavigationWarning', false)
        ->assertDispatched('exam-navigation-confirmed');

    expect(SuspiciousActivity::query()->where('event_type', 'browser_back')->exists())->toBeTrue();
});

test('a timed exam exposes its expiry to the browser countdown', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'time_limit_minutes' => 30,
        'require_fullscreen' => false,
        'show_index_number_field' => false,
    ]);
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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertDispatched('exam-attempt-started');

    expect($component->get('attempt.expires_at'))->not->toBeNull();
});

test('multiple-choice selections are tracked independently for each option', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'all',
        'show_index_number_field' => false,
    ]);

    $question = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'allows_multiple_selection' => true,
        'position' => 1,
    ]);

    $selectedOption = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'position' => 1,
        'label' => 'Selected option',
        'is_correct' => true,
    ]);

    $unselectedOption = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'position' => 2,
        'label' => 'Unselected option',
    ]);

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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->set("responses.{$question->id}.selected_options.{$selectedOption->id}", true)
        ->assertSet("responses.{$question->id}.selected_options.{$selectedOption->id}", true)
        ->call('submitExam', app(SubmitExamAttemptAction::class));

    $answer = ExamAnswer::query()
        ->where('exam_attempt_id', $component->get('attempt.id'))
        ->where('question_id', $question->id)
        ->sole();

    expect($answer->selected_option_ids)
        ->toBe([$selectedOption->id])
        ->not->toContain($unselectedOption->id);
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

test('student email addresses must include a valid domain suffix', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
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
        ->set('candidate.student_email', 'k@gma')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertHasErrors(['candidate.student_email']);
});

test('student names only allow letters and common name punctuation', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student 123')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertHasErrors(['candidate.student_name']);
});

test('student details are trimmed before validation and starting an exam', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', '  Student   One  ')
        ->set('candidate.student_email', '  student@example.com  ')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertSet('attempt.student_name', 'Student One')
        ->assertSet('attempt.student_email', 'student@example.com');
});

test('fullscreen is required before a student can start a fullscreen exam', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'require_fullscreen' => true,
        'show_index_number_field' => false,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSee('This browser cannot enter fullscreen mode.')
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertHasErrors(['fullscreen'])
        ->set('fullscreenConfirmed', true)
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertSet('attempt.student_email', 'student@example.com');
});

test('an unsupported fullscreen browser can start an exam and is warned when leaving it', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'require_fullscreen' => true,
        'show_index_number_field' => false,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    $component = Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSee('This browser cannot enter fullscreen mode.')
        ->set('fullscreenUnsupported', true)
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertSet('attempt.student_email', 'student@example.com')
        ->call('triggerFullscreenFallbackWarning')
        ->assertSet('showFullscreenExitWarning', true)
        ->assertSee('You moved away from the exam.')
        ->call('submitForFullscreenExit', app(SubmitExamAttemptAction::class))
        ->assertSet('submitted', true);

    $this->assertDatabaseHas('exam_attempts', [
        'id' => $component->get('attempt.id'),
        'status' => ExamAttempt::STATUS_AUTO_SUBMITTED,
    ]);
});

test('leaving fullscreen warns the student and auto-submits the attempt', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'require_fullscreen' => true,
        'show_index_number_field' => false,
    ]);
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
        ->set('fullscreenConfirmed', true)
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->call('logClientEvent', 'fullscreen_exit')
        ->assertSet('showFullscreenExitWarning', true)
        ->call('submitForFullscreenExit', app(SubmitExamAttemptAction::class))
        ->assertSet('submitted', true);

    $this->assertDatabaseHas('exam_attempts', [
        'id' => $component->get('attempt.id'),
        'status' => ExamAttempt::STATUS_AUTO_SUBMITTED,
    ]);
});

test('a shared link blocks a student email that has already completed the exam', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
    ]);
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
        'email' => null,
        'max_attempts' => 0,
    ]);
    ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'exam_access_link_id' => $link->id,
        'student_email' => 'Student@Example.com',
        'status' => ExamAttempt::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertHasErrors(['candidate.student_email']);

    expect($link->attempts()->count())->toBe(1);
});

test('leaving an exam tab is logged as suspicious activity', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
    ]);
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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->call('logClientEvent', 'tab_hidden');

    $this->assertDatabaseHas('suspicious_activities', [
        'exam_attempt_id' => $component->get('attempt.id'),
        'event_type' => 'tab_hidden',
        'severity' => 'medium',
    ]);

    expect(SuspiciousActivity::query()->count())->toBe(1);
});

test('copy and paste attempts are logged as suspicious activity', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'show_index_number_field' => false,
    ]);
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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->call('logClientEvent', 'copy', 'Copied answer text', ['text/plain'])
        ->call('logClientEvent', 'paste', 'Pasted answer text', ['text/plain', 'text/html']);

    $this->assertDatabaseHas('suspicious_activities', [
        'exam_attempt_id' => $component->get('attempt.id'),
        'event_type' => 'copy',
        'severity' => 'medium',
    ]);

    $copyActivity = SuspiciousActivity::query()
        ->where('exam_attempt_id', $component->get('attempt.id'))
        ->where('event_type', 'copy')
        ->firstOrFail();

    expect($copyActivity->context)->toMatchArray([
        'clipboard_text' => 'Copied answer text',
        'clipboard_types' => ['text/plain'],
    ]);
    $this->assertDatabaseHas('suspicious_activities', [
        'exam_attempt_id' => $component->get('attempt.id'),
        'event_type' => 'paste',
        'severity' => 'medium',
    ]);
});

test('shuffled exams persist a question order for each attempt', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'shuffle_questions' => true,
        'show_index_number_field' => false,
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

test('question banks randomly select and persist the configured number of questions per attempt', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'questions_per_attempt' => 2,
        'shuffle_questions' => false,
        'show_index_number_field' => false,
    ]);
    $questions = Question::factory()->count(4)->sequence(
        ['exam_id' => $exam->id, 'position' => 1, 'points' => 1],
        ['exam_id' => $exam->id, 'position' => 2, 'points' => 2],
        ['exam_id' => $exam->id, 'position' => 3, 'points' => 3],
        ['exam_id' => $exam->id, 'position' => 4, 'points' => 4],
    )->create();
    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
    ]);

    $component = Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSee('Questions: 2')
        ->set('candidate.student_name', 'Student One')
        ->set('candidate.student_email', 'student@example.com')
        ->call('startAttempt', app(StartExamAttemptAction::class));

    $attempt = ExamAttempt::query()->findOrFail($component->get('attempt.id'));
    $selectedQuestionIds = $attempt->meta['question_order'] ?? [];

    expect($selectedQuestionIds)
        ->toHaveCount(2)
        ->and(collect($selectedQuestionIds)->diff($questions->pluck('id')))
        ->toBeEmpty()
        ->and((float) $attempt->max_score)
        ->toBe((float) $questions->whereIn('id', $selectedQuestionIds)->sum('points'));
});

test('expired exams show an unavailable message instead of a forbidden response', function () {
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
    ])
        ->assertSee('This link has expired.')
        ->assertSee('This exam is no longer accepting responses because its availability period has ended.');
});

test('expired access links show an unavailable message', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'expires_at' => now()->addDay(),
    ]);

    $link = ExamAccessLink::factory()->create([
        'exam_id' => $exam->id,
        'created_by' => $examiner->id,
        'expires_at' => now()->subMinute(),
    ]);

    Livewire::test(TakeExam::class, [
        'publicKey' => $link->public_key,
        'accessToken' => $link->access_token,
    ])
        ->assertSee('This link has expired.')
        ->assertSee('This exam link has expired and is no longer accepting responses.');
});

test('students cannot go back and must answer each question before proceeding when back navigation is disabled', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'one_at_a_time',
        'allow_back_navigation' => false,
        'show_index_number_field' => false,
    ]);

    $firstQuestion = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
    ]);

    $firstOption = QuestionOption::factory()->create([
        'question_id' => $firstQuestion->id,
        'label' => 'First answer',
        'is_correct' => true,
    ]);

    $secondQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'accepted_answers' => ['Laravel'],
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
        ->assertDontSee('Previous')
        ->call('nextQuestion', app(SaveExamAnswerAction::class))
        ->assertHasErrors(['currentQuestionResponse'])
        ->set("responses.{$firstQuestion->id}.selected_option_id", $firstOption->id)
        ->assertSet("responses.{$firstQuestion->id}.selected_option_id", $firstOption->id)
        ->call('nextQuestion', app(SaveExamAnswerAction::class))
        ->assertSet('currentQuestionIndex', 1)
        ->assertDontSee('Next')
        ->assertDontSee('Previous')
        ->assertSee('No backtracking')
        ->assertSee($secondQuestion->prompt);
});

test('next saves the complete response draft before advancing', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'one_at_a_time',
        'allow_back_navigation' => false,
        'show_index_number_field' => false,
    ]);
    $firstQuestion = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
    ]);
    $firstOption = QuestionOption::factory()->create([
        'question_id' => $firstQuestion->id,
        'label' => 'First answer',
        'is_correct' => true,
    ]);
    Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'accepted_answers' => ['Laravel'],
    ]);
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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->call('syncAndNext', [
            $firstQuestion->id => ['selected_option_id' => $firstOption->id],
        ])
        ->assertSet('currentQuestionIndex', 1);

    $this->assertDatabaseHas('exam_answers', [
        'exam_attempt_id' => $component->get('attempt.id'),
        'question_id' => $firstQuestion->id,
    ]);
});

test('activity is logged even when fullscreen is not required', function () {
    $examiner = User::factory()->create();
    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'require_fullscreen' => false,
        'show_index_number_field' => false,
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
        ->call('logClientEvent', 'tab_hidden');

    expect(SuspiciousActivity::query()->where('event_type', 'tab_hidden')->exists())->toBeTrue();
});

test('per-question timer advances to the next question and auto-submits the last question when time runs out', function () {
    $examiner = User::factory()->create();

    $exam = Exam::factory()->create([
        'created_by' => $examiner->id,
        'display_mode' => 'one_at_a_time',
        'allow_back_navigation' => false,
        'show_index_number_field' => false,
        'settings' => ['enable_per_question_timer' => true],
    ]);

    $firstQuestion = Question::factory()->create([
        'exam_id' => $exam->id,
        'type' => Question::TYPE_MULTIPLE_CHOICE,
        'position' => 1,
        'settings' => ['time_limit_seconds' => 10],
    ]);

    QuestionOption::factory()->create([
        'question_id' => $firstQuestion->id,
        'label' => 'First option',
        'is_correct' => true,
    ]);

    $secondQuestion = Question::factory()->fillIn()->create([
        'exam_id' => $exam->id,
        'position' => 2,
        'accepted_answers' => ['Laravel'],
        'settings' => ['time_limit_seconds' => 10],
    ]);

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
        ->call('startAttempt', app(StartExamAttemptAction::class))
        ->assertSee('Question timer:')
        ->assertSee('Moves on in')
        ->assertSet('currentQuestionIndex', 0);

    $attemptId = $component->get('attempt.id');

    $attempt = ExamAttempt::query()->findOrFail($attemptId);
    $meta = $attempt->meta ?? [];
    $meta['current_question_timer']['expires_at'] = now()->subSecond()->toIso8601String();
    $attempt->update(['meta' => $meta]);

    $component
        ->call('handleQuestionTimerExpired', app(SaveExamAnswerAction::class), app(SubmitExamAttemptAction::class))
        ->assertSet('currentQuestionIndex', 1)
        ->assertSee($secondQuestion->prompt);

    $attempt = ExamAttempt::query()->findOrFail($attemptId);
    $meta = $attempt->meta ?? [];
    $meta['current_question_timer']['expires_at'] = now()->subSecond()->toIso8601String();
    $attempt->update(['meta' => $meta]);

    $component
        ->call('handleQuestionTimerExpired', app(SaveExamAnswerAction::class), app(SubmitExamAttemptAction::class))
        ->assertSet('submitted', true);

    $this->assertDatabaseHas('exam_attempts', [
        'id' => $attemptId,
        'status' => ExamAttempt::STATUS_AUTO_SUBMITTED,
    ]);
    expect(ExamAttempt::query()->findOrFail($attemptId)->automaticSubmissionReason())
        ->toBe(ExamAttempt::AUTO_SUBMISSION_REASON_QUESTION_TIME_EXPIRED);
});
