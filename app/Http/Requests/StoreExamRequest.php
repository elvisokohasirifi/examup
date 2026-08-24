<?php

namespace App\Http\Requests;

use App\Actions\Exams\ImportExamQuestionsFromTextAction;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class StoreExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $exam = $this->route('id');

        if (is_string($exam)) {
            $exam = Exam::query()->find($exam);
        }

        if (! $exam instanceof Exam) {
            $exam = null;
        }

        return $exam === null
            ? (bool) $this->user()?->can('create', Exam::class)
            : (bool) $this->user()?->can('update', $exam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'display_mode' => ['required', Rule::in(['all', 'one_at_a_time'])],
            'allow_back_navigation' => ['boolean'],
            'shuffle_questions' => ['boolean'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'autosave_interval_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'show_score_to_student' => ['boolean'],
            'show_correct_answers_to_student' => ['boolean'],
            'show_index_number_field' => ['boolean'],
            'disable_copy_paste' => ['boolean'],
            'is_published' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'questions_import' => ['nullable', 'file', 'mimes:txt', 'max:1024'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'uuid'],
            'questions.*.type' => ['required', Rule::in([Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_FILL_IN])],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.help_text' => ['nullable', 'string'],
            'questions.*.points' => ['required', 'numeric', 'min:0.25'],
            'questions.*.allows_multiple_selection' => ['nullable', 'boolean'],
            'questions.*.accepted_answers' => ['nullable', 'array'],
            'questions.*.accepted_answers.*' => ['required', 'string', 'max:255'],
            'questions.*.question_options' => ['nullable', 'array'],
            'questions.*.question_options.*.id' => ['nullable', 'uuid'],
            'questions.*.question_options.*.label' => ['nullable', 'string', 'max:255'],
            'questions.*.question_options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $questions = $this->input('questions', []);

        if ($this->hasFile('questions_import')) {
            try {
                $questions = app(ImportExamQuestionsFromTextAction::class)->handle($this->file('questions_import'));
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'questions_import' => $exception->getMessage(),
                ]);
            }
        }

        $normalizedQuestions = collect($questions)
            ->map(function (array $question): array {
                $acceptedAnswers = $question['accepted_answers'] ?? [];
                if (is_string($acceptedAnswers)) {
                    $acceptedAnswers = preg_split('/\r\n|\r|\n/', $acceptedAnswers) ?: [];
                }

                $questionOptions = collect($question['question_options'] ?? [])
                    ->map(function (array $option): array {
                        $label = trim((string) ($option['label'] ?? ''));

                        return [
                            'id' => $option['id'] ?? null,
                            'label' => $label,
                            'value' => $label,
                            'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOL),
                        ];
                    })
                    ->filter(fn (array $option): bool => $option['label'] !== '')
                    ->values()
                    ->all();

                return [
                    'id' => $question['id'] ?? null,
                    'type' => $question['type'] ?? null,
                    'prompt' => trim((string) ($question['prompt'] ?? '')),
                    'help_text' => trim((string) ($question['help_text'] ?? '')),
                    'points' => $question['points'] ?? null,
                    'allows_multiple_selection' => filter_var($question['allows_multiple_selection'] ?? false, FILTER_VALIDATE_BOOL),
                    'accepted_answers' => collect($acceptedAnswers)
                        ->map(fn ($answer) => trim((string) $answer))
                        ->filter()
                        ->values()
                        ->all(),
                    'question_options' => $questionOptions,
                ];
            })
            ->filter(fn (array $question): bool => $question['prompt'] !== '')
            ->values()
            ->all();

        $booleanFields = [
            'allow_back_navigation' => true,
            'shuffle_questions' => false,
            'show_score_to_student' => false,
            'show_correct_answers_to_student' => false,
            'show_index_number_field' => false,
            'disable_copy_paste' => false,
            'is_published' => false,
        ];

        $normalizedBooleanFields = collect($booleanFields)
            ->mapWithKeys(fn (bool $default, string $field): array => [
                $field => filter_var($this->input($field, $default), FILTER_VALIDATE_BOOL),
            ])
            ->all();

        $this->merge([
            ...$normalizedBooleanFields,
            'questions' => $normalizedQuestions,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('questions', []) as $index => $question) {
                    $questionNumber = $index + 1;

                    if (($question['type'] ?? null) === Question::TYPE_MULTIPLE_CHOICE) {
                        $options = collect($question['question_options'] ?? []);

                        if ($options->isEmpty()) {
                            $validator->errors()->add("questions.$index.question_options", "Question {$questionNumber} needs at least one option.");
                        }

                        if ($options->where('is_correct', true)->isEmpty()) {
                            $validator->errors()->add("questions.$index.question_options", "Question {$questionNumber} needs at least one correct option.");
                        }
                    }

                    if (($question['type'] ?? null) === Question::TYPE_FILL_IN && empty($question['accepted_answers'])) {
                        $validator->errors()->add("questions.$index.accepted_answers", "Question {$questionNumber} needs at least one accepted answer.");
                    }
                }
            },
        ];
    }
}
