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

        return view('admin.exams.attempt-show', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $exam->questions()
                ->with('options')
                ->get(),
            'answersByQuestion' => $attempt->answers->keyBy('question_id'),
        ]);
    }
}
