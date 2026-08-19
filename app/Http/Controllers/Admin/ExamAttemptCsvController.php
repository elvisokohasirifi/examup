<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\Question;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamAttemptCsvController extends Controller
{
    public function __invoke(Exam $exam): StreamedResponse
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        $exam->loadMissing([
            'questions.options',
            'attempts.accessLink',
            'attempts.answers',
        ]);

        $questions = $exam->questions->values();

        return response()->streamDownload(function () use ($exam, $questions): void {
            $handle = fopen('php://output', 'wb');
            $columns = [
                'student_name',
                'student_email',
                'student_index_number',
                'status',
                'score',
                'score_percentage',
                'duration_seconds',
                'started_at',
                'submitted_at',
            ];

            foreach ($questions as $questionIndex => $question) {
                $columns[] = 'Q'.($questionIndex + 1).': '.$question->prompt;
            }

            fputcsv($handle, $columns);

            foreach ($exam->attempts->reject(fn ($attempt) => $attempt->accessLink?->isPreview()) as $attempt) {
                $row = [
                    $attempt->student_name,
                    $attempt->student_email,
                    $attempt->student_index_number,
                    $attempt->status,
                    $attempt->score,
                    $attempt->score_percentage,
                    $attempt->duration_seconds,
                    $attempt->started_at,
                    $attempt->submitted_at,
                ];

                $answersByQuestion = $attempt->answers->keyBy('question_id');

                foreach ($questions as $question) {
                    $row[] = $this->formatAnswerForExport(
                        $question,
                        $answersByQuestion->get($question->id),
                    );
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'exam-'.$exam->id.'-results.csv');
    }

    private function formatAnswerForExport(Question $question, ?ExamAnswer $answer): string
    {
        if (! $answer instanceof ExamAnswer) {
            return '';
        }

        if ($question->isMultipleChoice()) {
            $selectedOptionIds = collect($answer->selected_option_ids ?? [])
                ->map(fn (mixed $value): string => (string) $value)
                ->filter()
                ->values();

            if ($selectedOptionIds->isEmpty()) {
                return '';
            }

            $labelsById = $question->options
                ->keyBy('id')
                ->map(fn ($option): string => (string) $option->label);

            return $selectedOptionIds
                ->map(fn (string $optionId): string => $labelsById->get($optionId, $optionId))
                ->implode(' | ');
        }

        return (string) ($answer->answer_text ?? '');
    }
}
