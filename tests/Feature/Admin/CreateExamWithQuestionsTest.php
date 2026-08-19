<?php

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
            'time_limit_minutes' => 60,
            'autosave_interval_seconds' => 15,
            'show_score_to_student' => 1,
            'show_correct_answers_to_student' => 0,
            'disable_copy_paste' => 1,
            'is_published' => 1,
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'Which organ pumps blood?',
                    'help_text' => 'Select the best answer.',
                    'points' => 2,
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
