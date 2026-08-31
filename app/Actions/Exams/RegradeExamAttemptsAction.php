<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamAttempt;

class RegradeExamAttemptsAction
{
    public function __construct(private SubmitExamAttemptAction $submitExamAttempt) {}

    public function handle(Exam $exam): int
    {
        $regradedAttempts = 0;

        $exam->attempts()
            ->current()
            ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_AUTO_SUBMITTED])
            ->where(function ($query): void {
                $query->whereDoesntHave('accessLink')
                    ->orWhereHas('accessLink', fn ($accessLinkQuery) => $accessLinkQuery->where(function ($metaQuery): void {
                        $metaQuery->whereNull('meta')
                            ->orWhere('meta->is_preview', false)
                            ->orWhereNull('meta->is_preview');
                    }));
            })
            ->lazyById()
            ->each(function (ExamAttempt $attempt) use (&$regradedAttempts): void {
                $this->submitExamAttempt->regrade($attempt);
                $regradedAttempts++;
            });

        return $regradedAttempts;
    }
}
