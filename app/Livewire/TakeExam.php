<?php

namespace App\Livewire;

use App\Actions\Exams\ResumeExamAttemptAction;
use App\Actions\Exams\SaveExamAnswerAction;
use App\Actions\Exams\StartExamAttemptAction;
use App\Actions\Exams\SubmitExamAttemptAction;
use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\SuspiciousActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class TakeExam extends Component
{
    public ExamAccessLink $accessLink;

    public Exam $exam;

    public ?ExamAttempt $attempt = null;

    public ?ExamAttempt $resumableAttempt = null;

    public array $candidate = [
        'student_name' => '',
        'student_email' => '',
        'student_index_number' => '',
    ];

    public array $responses = [];

    public int $currentQuestionIndex = 0;

    public bool $submitted = false;

    public bool $isUnavailable = false;

    public string $unavailableMessage = '';

    public function mount(
        string $publicKey,
        string $accessToken,
        ResumeExamAttemptAction $resumeExamAttempt,
        SubmitExamAttemptAction $submitExamAttempt,
    ): void {
        $this->accessLink = ExamAccessLink::query()
            ->where('public_key', $publicKey)
            ->where('access_token', $accessToken)
            ->with(['exam.questions.options'])
            ->firstOrFail();

        $this->exam = $this->accessLink->exam;
        $this->candidate['student_email'] = $this->accessLink->email ?? '';

        $storedAttempt = $resumeExamAttempt->fromSession(
            $this->accessLink,
            session($this->attemptSessionKey()),
        ) ?? $resumeExamAttempt->fromRecoveryUrl(
            $this->accessLink,
            request()->query('attempt'),
        );

        if (filled($this->accessLink->email)) {
            $this->attempt = $storedAttempt ?? $resumeExamAttempt->activeForIndividualLink($this->accessLink);
        } elseif ($storedAttempt !== null && ! $storedAttempt->isFinished()) {
            $this->resumableAttempt = $storedAttempt;
        } else {
            $this->attempt = $storedAttempt;
        }

        if ($this->attempt !== null) {
            $this->rememberAttempt();
            $this->restoreAttemptState();

            if (! $this->attempt->isFinished() && $this->attempt->expires_at?->isPast()) {
                $this->attempt = $submitExamAttempt->handle($this->attempt, true);
                $this->submitted = true;
            }

            return;
        }

        $this->ensureExamIsAvailable();
    }

    public function startAttempt(StartExamAttemptAction $startExamAttempt): void
    {
        if ($this->attempt !== null) {
            return;
        }

        if (! $this->ensureExamIsAvailable()) {
            return;
        }

        $rules = [
            'candidate.student_name' => $this->studentNameRules(),
            'candidate.student_email' => $this->studentEmailRules(),
        ];

        $rules['candidate.student_index_number'] = $this->exam->show_index_number_field
            ? ['required', 'string', 'max:255']
            : ['nullable', 'string', 'max:255'];

        $this->validate($rules);

        if (blank($this->accessLink->email)) {
            $existingAttempt = $this->accessLink->attemptForStudentEmail($this->candidate['student_email']);

            if ($existingAttempt !== null) {
                if (! $existingAttempt->isFinished()) {
                    $this->resumableAttempt = $existingAttempt;
                    $this->addError('candidate.student_email', 'An unfinished attempt already exists for this email. Resume it to continue.');

                    return;
                }

                $this->addError('candidate.student_email', 'This email address has already completed this exam.');

                return;
            }
        }

        if (! $this->ensureExamIsAvailable()) {
            return;
        }

        $this->attempt = $startExamAttempt->handle($this->accessLink, $this->candidate, request());
        $this->rememberAttempt();
        $this->dispatch('exam-attempt-started', attemptId: $this->attempt->id);
    }

    public function resumeAttempt(): void
    {
        if ($this->resumableAttempt === null) {
            return;
        }

        $this->validate([
            'candidate.student_email' => $this->studentEmailRules(),
        ]);

        if (! hash_equals(
            Str::of((string) $this->resumableAttempt->student_email)->lower()->trim()->toString(),
            Str::of($this->candidate['student_email'])->lower()->trim()->toString(),
        )) {
            $this->addError('candidate.student_email', 'Enter the email address used when this exam was started.');

            return;
        }

        $this->attempt = $this->resumableAttempt;
        $this->resumableAttempt = null;
        $this->rememberAttempt();
        $this->restoreAttemptState();
        $this->dispatch('exam-attempt-started', attemptId: $this->attempt->id);
    }

    public function startNewAttempt(): void
    {
        $this->resumableAttempt = null;
        $this->resetErrorBag('candidate.student_email');
    }

    public function updatedResponses(mixed $value, ?string $key, SaveExamAnswerAction $saveExamAnswer): void
    {
        if ($this->attempt === null || $this->attempt->isFinished()) {
            return;
        }

        if ($key === null || $key === '') {
            return;
        }

        $questionId = explode('.', $key)[0];
        $this->saveResponseForQuestion($questionId, $saveExamAnswer);
    }

    public function previousQuestion(SaveExamAnswerAction $saveExamAnswer): void
    {
        if (! $this->canGoBack()) {
            return;
        }

        $this->saveCurrentQuestion($saveExamAnswer);
        $this->currentQuestionIndex = max($this->currentQuestionIndex - 1, 0);
        $this->persistCurrentQuestionIndex();
    }

    public function nextQuestion(SaveExamAnswerAction $saveExamAnswer): void
    {
        if (! $this->canAdvanceFromCurrentQuestion()) {
            $this->addError('currentQuestionResponse', 'Answer this question before continuing. You cannot go back once you move on.');

            return;
        }

        $this->resetErrorBag('currentQuestionResponse');
        $this->saveCurrentQuestion($saveExamAnswer);
        $this->currentQuestionIndex = min($this->currentQuestionIndex + 1, max($this->questions->count() - 1, 0));
        $this->persistCurrentQuestionIndex();
    }

    public function refreshAttemptState(SubmitExamAttemptAction $submitExamAttempt): void
    {
        if ($this->attempt === null || $this->attempt->isFinished()) {
            return;
        }

        $this->attempt->refresh();
        $this->exam->refresh();

        if ($this->attempt->expires_at?->isPast() || $this->exam->hasExpired()) {
            $this->submitExam($submitExamAttempt, true);
        }
    }

    public function submitExam(SubmitExamAttemptAction $submitExamAttempt, bool $automatic = false): void
    {
        if ($this->attempt === null || $this->attempt->isFinished()) {
            return;
        }

        if (! $automatic && ! $this->canSubmitCurrentState()) {
            $message = $this->exam->display_mode === 'one_at_a_time' && ! $this->exam->allow_back_navigation
                ? 'Answer this question before submitting. You cannot go back once you move on.'
                : 'Answer this question before submitting.';

            $this->addError('currentQuestionResponse', $message);

            return;
        }

        foreach ($this->questions as $question) {
            app(SaveExamAnswerAction::class)->handle($this->attempt, $question, $this->payloadForQuestion($question));
        }

        $this->resetErrorBag('currentQuestionResponse');
        $this->attempt = $submitExamAttempt->handle($this->attempt, $automatic);
        $this->submitted = true;
    }

    public function logClientEvent(string $eventType): void
    {
        if ($this->attempt === null) {
            return;
        }

        if (! in_array($eventType, ['copy', 'paste', 'blur', 'tab_hidden', 'window_blur'], true)) {
            return;
        }

        SuspiciousActivity::create([
            'exam_attempt_id' => $this->attempt->id,
            'event_type' => $eventType,
            'severity' => in_array($eventType, ['copy', 'paste', 'blur', 'tab_hidden', 'window_blur'], true) ? 'medium' : 'low',
            'details' => 'Client-side deterrent event captured during exam attempt.',
            'context' => [
                'question_index' => $this->currentQuestionIndex,
                'captured_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function getQuestionsProperty(): Collection
    {
        $questions = $this->exam->questions->values();
        $questionOrder = collect($this->attempt?->meta['question_order'] ?? [])
            ->filter(fn ($questionId) => is_string($questionId) && $questionId !== '')
            ->values();

        if ($questionOrder->isEmpty()) {
            return $questions;
        }

        $questionsById = $questions->keyBy('id');

        return $questionOrder
            ->map(fn (string $questionId) => $questionsById->get($questionId))
            ->filter()
            ->values();
    }

    public function getVisibleQuestionsProperty(): Collection
    {
        if ($this->exam->display_mode === 'all') {
            return $this->questions;
        }

        return collect([$this->questions->get($this->currentQuestionIndex)])->filter();
    }

    public function getTimeRemainingProperty(): ?int
    {
        if ($this->attempt?->expires_at === null) {
            return null;
        }

        return max(now()->diffInSeconds($this->attempt->expires_at, false), 0);
    }

    public function render()
    {
        return view('livewire.take-exam')
            ->layout('components.layouts.exam', ['title' => $this->exam->title]);
    }

    private function saveCurrentQuestion(SaveExamAnswerAction $saveExamAnswer): void
    {
        $question = $this->questions->get($this->currentQuestionIndex);

        if ($question instanceof Question) {
            $this->saveResponseForQuestion($question->id, $saveExamAnswer);
        }
    }

    private function saveResponseForQuestion(string $questionId, SaveExamAnswerAction $saveExamAnswer): void
    {
        $question = $this->questions->firstWhere('id', $questionId);

        if (! $question instanceof Question || $this->attempt === null) {
            return;
        }

        $saveExamAnswer->handle($this->attempt, $question, $this->payloadForQuestion($question));
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadForQuestion(Question $question): array
    {
        $response = $this->responses[$question->id] ?? [];

        if ($question->isMultipleChoice()) {
            $selectedOptionIds = $question->allows_multiple_selection
                ? collect($response['selected_options'] ?? [])
                    ->filter(fn (mixed $isSelected): bool => filter_var($isSelected, FILTER_VALIDATE_BOOL))
                    ->keys()
                    ->map(fn (mixed $optionId): string => (string) $optionId)
                    ->values()
                    ->all()
                : array_values(array_filter([(string) ($response['selected_option_id'] ?? '')]));

            return [
                'selected_option_ids' => $selectedOptionIds,
            ];
        }

        return [
            'answer_text' => $this->normalizeAnswerText((string) ($response['answer_text'] ?? '')),
        ];
    }

    private function canGoBack(): bool
    {
        return $this->exam->display_mode === 'one_at_a_time'
            && $this->exam->allow_back_navigation
            && $this->currentQuestionIndex > 0;
    }

    private function canAdvanceFromCurrentQuestion(): bool
    {
        if ($this->exam->display_mode !== 'one_at_a_time' || $this->exam->allow_back_navigation) {
            return true;
        }

        $question = $this->questions->get($this->currentQuestionIndex);

        return $question instanceof Question && $this->questionHasResponse($question);
    }

    private function canSubmitCurrentState(): bool
    {
        if ($this->exam->display_mode !== 'one_at_a_time' || $this->exam->allow_back_navigation) {
            return true;
        }

        $question = $this->questions->get($this->currentQuestionIndex);

        return $question instanceof Question && $this->questionHasResponse($question);
    }

    private function questionHasResponse(Question $question): bool
    {
        $response = $this->responses[$question->id] ?? [];

        if ($question->isMultipleChoice()) {
            if ($question->allows_multiple_selection) {
                return collect($response['selected_options'] ?? [])
                    ->filter(fn (mixed $isSelected): bool => filter_var($isSelected, FILTER_VALIDATE_BOOL))
                    ->isNotEmpty();
            }

            return filled($response['selected_option_id'] ?? null);
        }

        return $this->normalizeAnswerText((string) ($response['answer_text'] ?? '')) !== '';
    }

    private function normalizeAnswerText(string $answer): string
    {
        return Str::of($answer)
            ->squish()
            ->toString();
    }

    /**
     * @return array<int, string>
     */
    private function studentEmailRules(): array
    {
        return [
            'required',
            'email:rfc',
            'max:255',
            'regex:/^[^@\\s]+@(?:[a-z0-9-]+\\.)+[a-z]{2,}$/i',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function studentNameRules(): array
    {
        return [
            'required',
            'string',
            'min:2',
            'max:255',
            "regex:/^[\\p{L}]+(?:[ .,'-][\\p{L}]+)*$/u",
        ];
    }

    private function ensureExamIsAvailable(): bool
    {
        if ($this->exam->hasExpired()) {
            $this->isUnavailable = true;
            $this->unavailableMessage = 'This exam is no longer accepting responses because its availability period has ended.';

            return false;
        }

        if (! $this->accessLink->isAvailable()) {
            $this->isUnavailable = true;
            $this->unavailableMessage = $this->accessLink->hasExpired()
                ? 'This exam link has expired and is no longer accepting responses.'
                : 'This exam link is no longer accepting responses.';

            return false;
        }

        return true;
    }

    private function attemptSessionKey(): string
    {
        return 'exam_attempts.'.$this->accessLink->id;
    }

    private function rememberAttempt(): void
    {
        if ($this->attempt !== null) {
            session()->put($this->attemptSessionKey(), $this->attempt->id);
        }
    }

    private function restoreAttemptState(): void
    {
        if ($this->attempt === null) {
            return;
        }

        $this->responses = $this->attempt->answers
            ->mapWithKeys(function ($answer): array {
                $question = $this->questions->firstWhere('id', $answer->question_id);

                if ($question?->isMultipleChoice()) {
                    $selectedOptionIds = collect($answer->selected_option_ids)
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        $answer->question_id => $question->allows_multiple_selection
                            ? ['selected_options' => array_fill_keys($selectedOptionIds, true)]
                            : ['selected_option_id' => $selectedOptionIds[0] ?? ''],
                    ];
                }

                return [$answer->question_id => ['answer_text' => $answer->answer_text ?? '']];
            })
            ->all();

        $maximumQuestionIndex = max($this->questions->count() - 1, 0);
        $this->currentQuestionIndex = min(
            max((int) data_get($this->attempt->meta, 'current_question_index', 0), 0),
            $maximumQuestionIndex,
        );
    }

    private function persistCurrentQuestionIndex(): void
    {
        if ($this->attempt === null || $this->attempt->isFinished()) {
            return;
        }

        $meta = $this->attempt->meta ?? [];
        $meta['current_question_index'] = $this->currentQuestionIndex;

        $this->attempt->updateQuietly(['meta' => $meta]);
    }
}
