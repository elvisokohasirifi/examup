<?php

use App\Actions\Exams\ImportExamQuestionsFromTextAction;
use App\Models\Question;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

test('it parses multiple choice and fill in questions from a text file', function () {
    $file = UploadedFile::fake()->createWithContent('questions.txt', <<<'TEXT'
TYPE: multiple_choice
POINTS: 2
MULTIPLE_CORRECT: true
PROMPT: Which are PHP frameworks?
HELP: Select every correct answer.
OPTIONS:
- Laravel
- Django
- Symfony
CORRECT:
- Laravel
- Symfony

---

TYPE: fill_in
POINTS: 1.5
PROMPT: What does HTML stand for?
CORRECT:
- HyperText Markup Language
- Hyper Text Markup Language
TEXT);

    $questions = app(ImportExamQuestionsFromTextAction::class)->handle($file);

    expect($questions)->toHaveCount(2)
        ->and($questions[0])->toMatchArray([
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'prompt' => 'Which are PHP frameworks?',
            'help_text' => 'Select every correct answer.',
            'points' => '2',
            'allows_multiple_selection' => true,
            'accepted_answers' => [],
        ])
        ->and($questions[0]['question_options'])->toBe([
            ['label' => 'Laravel', 'is_correct' => true],
            ['label' => 'Django', 'is_correct' => false],
            ['label' => 'Symfony', 'is_correct' => true],
        ])
        ->and($questions[1])->toMatchArray([
            'type' => Question::TYPE_FILL_IN,
            'prompt' => 'What does HTML stand for?',
            'points' => '1.5',
            'allows_multiple_selection' => false,
            'accepted_answers' => [
                'HyperText Markup Language',
                'Hyper Text Markup Language',
            ],
            'question_options' => [],
        ]);
});

test('it rejects a multiple choice answer not present in its options', function () {
    $file = UploadedFile::fake()->createWithContent('questions.txt', <<<'TEXT'
TYPE: multiple_choice
POINTS: 2
PROMPT: Which is correct?
OPTIONS:
- First option
CORRECT:
- Missing option
TEXT);

    app(ImportExamQuestionsFromTextAction::class)->handle($file);
})->throws(InvalidArgumentException::class, 'CORRECT answer that is not listed in OPTIONS');
