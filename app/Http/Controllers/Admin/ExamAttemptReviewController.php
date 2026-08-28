<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Contracts\View\View;

class ExamAttemptReviewController extends Controller
{
    public function __invoke(Exam $exam, ExamAttempt $attempt): View
    {
        abort_unless(backpack_user()->can('view', $exam), 403);
        abort_unless($attempt->exam_id === $exam->id, 404);

        $attempt->loadMissing([
            'answers.question.options',
            'suspiciousActivities',
        ]);

        $allQuestions = $exam->questions()
            ->with('options')
            ->get()
            ->keyBy('id');
        $questionOrder = collect(data_get($attempt->meta, 'question_order', []))
            ->filter(fn (mixed $questionId): bool => is_string($questionId) && $questionId !== '')
            ->values();
        $questions = $questionOrder->isEmpty()
            ? $allQuestions->values()
            : $questionOrder
                ->map(fn (string $questionId) => $allQuestions->get($questionId))
                ->filter()
                ->values();

        return view('admin.exams.attempt-show', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'answersByQuestion' => $attempt->answers->keyBy('question_id'),
        ]);
    }
}
