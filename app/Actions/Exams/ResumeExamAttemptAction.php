<?php

namespace App\Actions\Exams;

use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;

class ResumeExamAttemptAction
{
    public function fromSession(ExamAccessLink $accessLink, ?string $attemptId): ?ExamAttempt
    {
        return $this->forAccessLink($accessLink, $attemptId);
    }

    public function fromRecoveryUrl(ExamAccessLink $accessLink, ?string $attemptId): ?ExamAttempt
    {
        return $this->forAccessLink($accessLink, $attemptId);
    }

    private function forAccessLink(ExamAccessLink $accessLink, ?string $attemptId): ?ExamAttempt
    {
        if (blank($attemptId)) {
            return null;
        }

        return $accessLink->attempts()
            ->with('answers')
            ->whereKey($attemptId)
            ->first();
    }

    public function activeForIndividualLink(ExamAccessLink $accessLink): ?ExamAttempt
    {
        if (blank($accessLink->email)) {
            return null;
        }

        return $accessLink->attempts()
            ->with('answers')
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();
    }
}
