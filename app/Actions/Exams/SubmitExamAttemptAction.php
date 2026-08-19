<?php

namespace App\Actions\Exams;

use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Str;

class SubmitExamAttemptAction
{
    public function handle(ExamAttempt $attempt, bool $automatic = false): ExamAttempt
    {
        $attempt->loadMissing('exam.questions.options', 'answers');

        $answersByQuestion = $attempt->answers->keyBy('question_id');
        $totalScore = 0.0;
        $maxScore = (float) $attempt->exam->questions->sum('points');

        foreach ($attempt->exam->questions as $question) {
            $answer = $answersByQuestion->get($question->id)
                ?? new ExamAnswer([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ]);

            [$isCorrect, $score, $gradedPayload] = $this->gradeQuestion($question, $answer);

            $answer->fill([
                'is_correct' => $isCorrect,
                'score' => $score,
                'graded_payload' => $gradedPayload,
                'answered_at' => $answer->answered_at ?? now(),
            ]);
            $answer->save();

            $totalScore += $score;
        }

        $startedAt = $attempt->started_at ?? $attempt->created_at;
        $submittedAt = now();

        $attempt->update([
            'status' => $automatic ? ExamAttempt::STATUS_AUTO_SUBMITTED : ExamAttempt::STATUS_SUBMITTED,
            'submitted_at' => $submittedAt,
            'auto_submitted_at' => $automatic ? $submittedAt : null,
            'duration_seconds' => $startedAt?->diffInSeconds($submittedAt),
            'score' => $totalScore,
            'max_score' => $maxScore,
            'score_percentage' => $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0,
        ]);

        return $attempt->fresh(['exam', 'answers.question']);
    }

    /**
     * @return array{0: bool, 1: float, 2: array<string, mixed>}
     */
    private function gradeQuestion(Question $question, ExamAnswer $answer): array
    {
        if ($question->isMultipleChoice()) {
            $selectedIds = collect($answer->selected_option_ids)
                ->filter()
                ->map(fn (string $id): string => (string) $id)
                ->sort()
                ->values();

            $correctIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn (string $id): string => (string) $id)
                ->sort()
                ->values();

            $isCorrect = $selectedIds->all() === $correctIds->all();

            return [
                $isCorrect,
                $isCorrect ? (float) $question->points : 0.0,
                [
                    'selected_option_ids' => $selectedIds->all(),
                    'correct_option_ids' => $correctIds->all(),
                ],
            ];
        }

        $normalizedAnswer = Str::lower(Str::squish((string) $answer->answer_text));
        $acceptedAnswers = $question->normalizedAcceptedAnswers();
        $isCorrect = $acceptedAnswers->contains($normalizedAnswer);

        return [
            $isCorrect,
            $isCorrect ? (float) $question->points : 0.0,
            [
                'normalized_answer' => $normalizedAnswer,
                'accepted_answers' => $acceptedAnswers->all(),
            ],
        ];
    }
}
