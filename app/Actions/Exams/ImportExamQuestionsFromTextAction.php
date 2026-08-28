<?php

namespace App\Actions\Exams;

use App\Models\Question;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportExamQuestionsFromTextAction
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new InvalidArgumentException('The question import file could not be read.');
        }

        return $this->fromText($contents);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fromText(string $contents): array
    {
        if (blank($contents)) {
            throw new InvalidArgumentException('The question import file is empty.');
        }

        $blocks = collect(preg_split('/^\s*---\s*$/m', trim($contents)) ?: [])
            ->map(fn (string $block): string => trim($block))
            ->filter()
            ->values();

        if ($blocks->isEmpty()) {
            throw new InvalidArgumentException('The question import file does not contain any question blocks.');
        }

        return $blocks
            ->map(fn (string $block, int $index): array => $this->parseQuestionBlock($block, $index + 1))
            ->all();
    }

    public function sample(): string
    {
        return <<<'TEXT'
TYPE: multiple_choice
POINTS: 2
MULTIPLE_CORRECT: false
PROMPT: What is the capital of Ghana?
HELP: Choose one answer.
OPTIONS:
- Accra
- Kumasi
- Tamale
- Cape Coast
CORRECT:
- Accra

---

TYPE: multiple_choice
POINTS: 3
MULTIPLE_CORRECT: true
PROMPT: Which are PHP frameworks?
OPTIONS:
- Laravel
- Django
- Symfony
- Rails
CORRECT:
- Laravel
- Symfony

---

TYPE: fill_in
POINTS: 2
PROMPT: What does HTML stand for?
HELP: Write the full phrase.
CORRECT:
- HyperText Markup Language
- Hyper Text Markup Language
TEXT;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseQuestionBlock(string $block, int $questionNumber): array
    {
        $fields = [
            'options' => [],
            'correct' => [],
        ];
        $activeList = null;

        foreach (preg_split('/\r\n|\r|\n/', $block) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(OPTIONS|CORRECT):\s*$/i', $line, $matches) === 1) {
                $activeList = Str::lower($matches[1]);

                continue;
            }

            if ($activeList !== null && str_starts_with($line, '-')) {
                $value = trim(Str::after($line, '-'));

                if ($value === '') {
                    throw new InvalidArgumentException("Question {$questionNumber} contains an empty {$activeList} entry.");
                }

                $fields[$activeList][] = $value;

                continue;
            }

            if (preg_match('/^(TYPE|POINTS|MULTIPLE_CORRECT|PROMPT|HELP):\s*(.+)$/i', $line, $matches) === 1) {
                $fields[Str::lower($matches[1])] = trim($matches[2]);
                $activeList = null;

                continue;
            }

            throw new InvalidArgumentException("Question {$questionNumber} has an unrecognized line: {$line}");
        }

        $type = $fields['type'] ?? null;
        $prompt = $fields['prompt'] ?? null;
        $points = $fields['points'] ?? null;

        if (! in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_FILL_IN], true)) {
            throw new InvalidArgumentException("Question {$questionNumber} must declare TYPE as multiple_choice or fill_in.");
        }

        if (blank($prompt)) {
            throw new InvalidArgumentException("Question {$questionNumber} must include a PROMPT.");
        }

        if (! is_numeric($points) || (float) $points < 0.25) {
            throw new InvalidArgumentException("Question {$questionNumber} must include POINTS of at least 0.25.");
        }

        $allowsMultipleSelection = $this->parseBoolean($fields['multiple_correct'] ?? 'false', $questionNumber);

        if ($type === Question::TYPE_FILL_IN) {
            if (empty($fields['correct'])) {
                throw new InvalidArgumentException("Question {$questionNumber} needs at least one CORRECT answer.");
            }

            return [
                'type' => $type,
                'prompt' => $prompt,
                'help_text' => $fields['help'] ?? null,
                'points' => $points,
                'allows_multiple_selection' => false,
                'accepted_answers' => $fields['correct'],
                'question_options' => [],
            ];
        }

        if (empty($fields['options'])) {
            throw new InvalidArgumentException("Question {$questionNumber} needs at least one OPTIONS entry.");
        }

        if (empty($fields['correct'])) {
            throw new InvalidArgumentException("Question {$questionNumber} needs at least one CORRECT entry.");
        }

        $unknownCorrectAnswers = collect($fields['correct'])
            ->diff($fields['options']);

        if ($unknownCorrectAnswers->isNotEmpty()) {
            throw new InvalidArgumentException("Question {$questionNumber} has a CORRECT answer that is not listed in OPTIONS.");
        }

        return [
            'type' => $type,
            'prompt' => $prompt,
            'help_text' => $fields['help'] ?? null,
            'points' => $points,
            'allows_multiple_selection' => $allowsMultipleSelection,
            'accepted_answers' => [],
            'question_options' => collect($fields['options'])
                ->map(fn (string $option): array => [
                    'label' => $option,
                    'is_correct' => in_array($option, $fields['correct'], true),
                ])
                ->all(),
        ];
    }

    private function parseBoolean(string $value, int $questionNumber): bool
    {
        $normalizedValue = Str::lower(trim($value));

        return match ($normalizedValue) {
            'true', '1', 'yes' => true,
            'false', '0', 'no' => false,
            default => throw new InvalidArgumentException("Question {$questionNumber} has an invalid MULTIPLE_CORRECT value."),
        };
    }
}
