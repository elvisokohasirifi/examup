<?php

namespace App\Console\Commands;

use App\Actions\Exams\AutoSubmitExpiredExamAttemptsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('exams:submit-expired')]
#[Description('Auto-submit exam attempts whose time limit has elapsed')]
class AutoSubmitExpiredExamAttempts extends Command
{
    public function handle(AutoSubmitExpiredExamAttemptsAction $autoSubmitExpiredExamAttempts): int
    {
        $submittedCount = $autoSubmitExpiredExamAttempts->handle();

        $this->info("Auto-submitted {$submittedCount} expired exam attempt(s).");

        return self::SUCCESS;
    }
}
