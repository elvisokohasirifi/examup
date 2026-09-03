<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('admin can create an exam with nested questions and options from the exam form', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post('/admin/exam', [
            'title' => 'Biology Midterm',
            'description' => 'Semester one exam',
            'instructions' => 'Answer all questions.',
            'display_mode' => 'all',
            'allow_back_navigation' => 1,
            'settings' => ['enable_per_question_timer' => 0],
            'shuffle_questions' => 1,
            'questions_per_attempt' => 1,
            'time_limit_minutes' => 60,
            'autosave_interval_seconds' => 15,
            'expires_at' => now()->addWeek()->toDateTimeString(),
            'show_score_to_student' => 'on',
            'show_correct_answers_to_student' => 'on',
            'show_index_number_field' => 'on',
            'disable_copy_paste' => 'on',
            'require_fullscreen' => 'on',
            'is_published' => 'on',
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'Which organ pumps blood?',
                    'help_text' => 'Select the best answer.',
                    'points' => 2,
                    'time_limit_seconds' => null,
                    'allows_multiple_selection' => 0,
                    'accepted_answers' => [],
                    'question_options' => [
                        ['label' => 'Heart', 'is_correct' => 1],
                        ['label' => 'Lung', 'is_correct' => 0],
                    ],
                ],
                [
                    'type' => 'fill_in',
                    'prompt' => 'DNA stands for?',
                    'help_text' => '',
                    'points' => 3,
                    'time_limit_seconds' => null,
                    'allows_multiple_selection' => 0,
                    'accepted_answers' => ['Deoxyribonucleic Acid'],
                    'question_options' => [],
                ],
            ],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('exams', [
        'title' => 'Biology Midterm',
        'created_by' => $admin->id,
        'allow_back_navigation' => 1,
        'shuffle_questions' => 1,
        'questions_per_attempt' => 1,
        'show_score_to_student' => 1,
        'show_correct_answers_to_student' => 1,
        'show_index_number_field' => 1,
        'disable_copy_paste' => 1,
        'require_fullscreen' => 1,
        'is_published' => 1,
    ]);

    $this->assertDatabaseHas('questions', [
        'prompt' => 'Which organ pumps blood?',
        'type' => 'multiple_choice',
        'position' => 1,
    ]);

    $this->assertDatabaseHas('question_options', [
        'label' => 'Heart',
        'value' => 'Heart',
        'is_correct' => 1,
        'position' => 1,
    ]);

    $this->assertDatabaseHas('questions', [
        'prompt' => 'DNA stands for?',
        'type' => 'fill_in',
        'position' => 2,
    ]);
});

test('admin can enable per-question timer for no-backtracking exams', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post('/admin/exam', [
            'title' => 'Timed Drill',
            'description' => 'Timed one-question flow',
            'instructions' => 'Keep moving.',
            'display_mode' => 'one_at_a_time',
            'allow_back_navigation' => 0,
            'settings' => ['enable_per_question_timer' => 1],
            'shuffle_questions' => 0,
            'questions_per_attempt' => null,
            'time_limit_minutes' => null,
            'autosave_interval_seconds' => 15,
            'show_score_to_student' => 0,
            'show_correct_answers_to_student' => 0,
            'show_index_number_field' => 0,
            'disable_copy_paste' => 1,
            'require_fullscreen' => 0,
            'is_published' => 1,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'First timed question',
                    'help_text' => '',
                    'points' => 1,
                    'time_limit_seconds' => 20,
                    'allows_multiple_selection' => 0,
                    'accepted_answers' => [],
                    'question_options' => [
                        ['label' => 'Answer A', 'is_correct' => 1],
                        ['label' => 'Answer B', 'is_correct' => 0],
                    ],
                ],
            ],
        ]);

    $response->assertRedirect();

    $exam = Exam::query()->where('title', 'Timed Drill')->firstOrFail();
    $question = $exam->questions()->sole();

    expect(data_get($exam->settings, 'enable_per_question_timer'))->toBeTrue()
        ->and(data_get($question->settings, 'time_limit_seconds'))->toBe(20);
});

test('admin defaults blank per-question limits from the exam time and questions per attempt', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post('/admin/exam', [
            'title' => 'Default Timed Drill',
            'display_mode' => 'one_at_a_time',
            'allow_back_navigation' => 0,
            'settings' => ['enable_per_question_timer' => 1],
            'shuffle_questions' => 0,
            'questions_per_attempt' => 3,
            'time_limit_minutes' => 60,
            'autosave_interval_seconds' => 15,
            'show_score_to_student' => 0,
            'show_correct_answers_to_student' => 0,
            'show_index_number_field' => 0,
            'disable_copy_paste' => 0,
            'require_fullscreen' => 0,
            'is_published' => 1,
            'questions' => collect(range(1, 3))
                ->map(fn (int $number): array => [
                    'type' => 'multiple_choice',
                    'prompt' => "Timed question {$number}",
                    'help_text' => '',
                    'points' => 1,
                    'time_limit_seconds' => null,
                    'allows_multiple_selection' => 0,
                    'accepted_answers' => [],
                    'question_options' => [
                        ['label' => 'Answer A', 'is_correct' => 1],
                        ['label' => 'Answer B', 'is_correct' => 0],
                    ],
                ])
                ->all(),
        ]);

    $response->assertRedirect();

    $exam = Exam::query()->where('title', 'Default Timed Drill')->firstOrFail();

    $exam->questions()->get()->each(
        fn (Question $question) => expect($question->timeLimitSeconds())->toBe(1200),
    );
});
