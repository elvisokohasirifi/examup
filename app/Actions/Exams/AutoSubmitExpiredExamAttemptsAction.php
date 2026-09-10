<?php

namespace App\Actions\Exams;

use App\Models\ExamAttempt;

class AutoSubmitExpiredExamAttemptsAction
{
    public function __construct(private SubmitExamAttemptAction $submitExamAttempt) {}

    public function handle(): int
    {
        $submittedCount = 0;

        $expiredAttempts = ExamAttempt::query()
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->cursor();

        foreach ($expiredAttempts as $expiredAttempt) {
            $attempt = $this->submitExamAttempt->handle(
                $expiredAttempt,
                true,
                ExamAttempt::AUTO_SUBMISSION_REASON_TIME_LIMIT_EXPIRED,
            );

            if ($attempt->status === ExamAttempt::STATUS_AUTO_SUBMITTED) {
                $submittedCount++;
            }
        }

        return $submittedCount;
    }
}
