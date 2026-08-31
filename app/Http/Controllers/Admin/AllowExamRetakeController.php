<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\AllowExamRetakeAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Notifications\ExamAccessLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;

class AllowExamRetakeController extends Controller
{
    public function __invoke(Exam $exam, ExamAttempt $attempt, AllowExamRetakeAction $allowExamRetake): RedirectResponse
    {
        abort_unless(backpack_user()->can('update', $exam), 403);
        abort_unless($attempt->exam_id === $exam->id, 404);

        $retakeLink = $allowExamRetake->handle($attempt, backpack_user());

        (new AnonymousNotifiable)
            ->route('mail', $retakeLink->email)
            ->notify(new ExamAccessLinkNotification($retakeLink, $retakeLink->examUrl()));

        $retakeLink->updateQuietly(['last_sent_at' => now()]);

        return to_route('admin.exams.results', $exam)
            ->with('success', "A retake link was emailed to {$attempt->student_email}.");
    }
}
