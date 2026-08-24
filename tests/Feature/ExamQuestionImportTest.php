<?php

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

test('admin can import questions from a text file when creating an exam', function () {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->createWithContent('questions.txt', <<<'TEXT'
TYPE: multiple_choice
POINTS: 2
PROMPT: Which organ pumps blood?
OPTIONS:
- Heart
- Lung
CORRECT:
- Heart

---

TYPE: fill_in
POINTS: 3
PROMPT: DNA stands for?
CORRECT:
- Deoxyribonucleic Acid
TEXT);

    $response = $this
        ->actingAs($admin)
        ->post('/admin/exam', [
            'title' => 'Imported Biology Midterm',
            'description' => 'Semester one exam',
            'instructions' => 'Answer all questions.',
            'display_mode' => 'all',
            'allow_back_navigation' => 1,
            'shuffle_questions' => 0,
            'autosave_interval_seconds' => 15,
            'show_score_to_student' => 1,
            'show_correct_answers_to_student' => 0,
            'show_index_number_field' => 0,
            'disable_copy_paste' => 1,
            'is_published' => 0,
            'questions_import' => $file,
            'questions' => [],
        ]);

    $response->assertRedirect();

    $exam = Exam::query()
        ->with('questions.options')
        ->where('title', 'Imported Biology Midterm')
        ->firstOrFail();

    expect($exam->created_by)->toBe($admin->id)
        ->and($exam->questions)->toHaveCount(2)
        ->and($exam->questions[0]->prompt)->toBe('Which organ pumps blood?')
        ->and($exam->questions[0]->options->map->only(['label', 'value', 'is_correct'])->all())->toBe([
            ['label' => 'Heart', 'value' => 'Heart', 'is_correct' => true],
            ['label' => 'Lung', 'value' => 'Lung', 'is_correct' => false],
        ])
        ->and($exam->questions[1]->accepted_answers)->toBe(['Deoxyribonucleic Acid']);
});

test('admin can download the question import sample file', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.exams.questions.sample'));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=exam-questions-sample.txt');

    expect($response->streamedContent())
        ->toContain('TYPE: multiple_choice')
        ->toContain('TYPE: fill_in')
        ->toContain('OPTIONS:');
});
