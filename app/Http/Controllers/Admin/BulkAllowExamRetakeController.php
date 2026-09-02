<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\AllowExamRetakeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkAllowExamRetakesRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Notifications\ExamAccessLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;

class BulkAllowExamRetakeController extends Controller
{
    public function __invoke(
        BulkAllowExamRetakesRequest $request,
        Exam $exam,
        AllowExamRetakeAction $allowExamRetake,
    ): RedirectResponse {
        $attemptsByEmail = $exam->attempts()
            ->current()
            ->whereIn('student_email', $request->emails())
            ->latest('submitted_at')
            ->get()
            ->filter(fn (ExamAttempt $attempt): bool => $attempt->isFinished() && ! $attempt->isSuperseded())
            ->groupBy(fn (ExamAttempt $attempt): string => Str::lower((string) $attempt->student_email))
            ->map->first();

        $sentCount = 0;

        foreach ($request->emails() as $email) {
            $attempt = $attemptsByEmail->get($email);

            if (! $attempt instanceof ExamAttempt) {
                continue;
            }

            $retakeLink = $allowExamRetake->handle($attempt, backpack_user());

            (new AnonymousNotifiable)
                ->route('mail', $retakeLink->email)
                ->notify(new ExamAccessLinkNotification($retakeLink, $retakeLink->examUrl()));

            $retakeLink->updateQuietly(['last_sent_at' => now()]);
            $sentCount++;
        }

        $skippedCount = count($request->emails()) - $sentCount;
        $message = "Retake links were emailed to {$sentCount} ".Str::plural('candidate', $sentCount).'.';

        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} ".Str::plural('email', $skippedCount).' with no eligible completed attempt.';
        }

        return to_route('admin.exams.results', $exam)->with('success', $message);
    }
}
