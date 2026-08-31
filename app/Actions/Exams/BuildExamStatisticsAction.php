<?php

namespace App\Actions\Exams;

use App\Models\Exam;

class BuildExamStatisticsAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Exam $exam): array
    {
        $exam->loadMissing('questions.options', 'attempts.answers', 'attempts.suspiciousActivities', 'attempts.accessLink');

        $attempts = $exam->attempts
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->whereNull('superseded_at')
            ->reject(fn ($attempt) => $attempt->accessLink?->isPreview())
            ->values();

        return [
            'attempt_count' => $attempts->count(),
            'average_score' => round((float) $attempts->avg('score'), 2),
            'highest_score' => round((float) $attempts->max('score'), 2),
            'lowest_score' => round((float) $attempts->min('score'), 2),
            'average_completion_time_seconds' => (int) round((float) $attempts->avg('duration_seconds')),
            'suspicious_events' => $attempts->sum(fn ($attempt) => $attempt->suspiciousActivities->count()),
            'questions' => $exam->questions->map(function ($question) use ($attempts): array {
                $questionAnswers = $attempts
                    ->flatMap->answers
                    ->where('question_id', $question->id)
                    ->values();

                if ($question->isMultipleChoice()) {
                    $totalResponses = max($questionAnswers->count(), 1);

                    return [
                        'question_id' => $question->id,
                        'prompt' => $question->prompt,
                        'correct_percentage' => round(($questionAnswers->where('is_correct', true)->count() / $totalResponses) * 100, 2),
                        'options' => $question->options->map(function ($option) use ($questionAnswers, $totalResponses): array {
                            $selectedCount = $questionAnswers
                                ->filter(fn ($answer) => in_array($option->id, $answer->selected_option_ids ?? [], true))
                                ->count();

                            return [
                                'option_id' => $option->id,
                                'label' => $option->label,
                                'selected_count' => $selectedCount,
                                'selected_percentage' => round(($selectedCount / $totalResponses) * 100, 2),
                                'is_correct' => $option->is_correct,
                            ];
                        })->all(),
                    ];
                }

                $fillInBreakdown = $questionAnswers
                    ->groupBy(fn ($answer) => mb_strtolower(trim((string) $answer->answer_text)))
                    ->map(fn ($group, $answerText) => [
                        'answer' => $answerText,
                        'count' => $group->count(),
                    ])
                    ->values()
                    ->sortByDesc('count')
                    ->take(10)
                    ->values()
                    ->all();

                $totalResponses = max($questionAnswers->count(), 1);

                return [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'correct_percentage' => round(($questionAnswers->where('is_correct', true)->count() / $totalResponses) * 100, 2),
                    'accepted_answers' => $question->accepted_answers ?? [],
                    'top_answers' => $fillInBreakdown,
                ];
            })->all(),
        ];
    }
}
