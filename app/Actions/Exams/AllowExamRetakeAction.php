<?php

namespace App\Actions\Exams;

use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllowExamRetakeAction
{
    public function handle(ExamAttempt $attempt, User $createdBy): ExamAccessLink
    {
        return DB::transaction(function () use ($attempt, $createdBy): ExamAccessLink {
            $attempt = ExamAttempt::query()
                ->lockForUpdate()
                ->with('exam')
                ->findOrFail($attempt->id);

            if (! $attempt->isFinished() || $attempt->isSuperseded() || blank($attempt->student_email)) {
                throw ValidationException::withMessages([
                    'attempt' => 'This attempt is not eligible for a retake.',
                ]);
            }

            $existingRetakeLink = ExamAccessLink::query()
                ->where('exam_id', $attempt->exam_id)
                ->where('meta->retake_for_attempt_id', $attempt->id)
                ->where('is_active', true)
                ->whereDoesntHave('attempts')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existingRetakeLink !== null) {
                return $existingRetakeLink->loadMissing('exam');
            }

            return $attempt->exam->accessLinks()->create([
                'created_by' => $createdBy->id,
                'email' => $attempt->student_email,
                'max_attempts' => 1,
                'send_email' => true,
                'is_active' => true,
                'expires_at' => $attempt->exam->expires_at,
                'meta' => [
                    'is_retake' => true,
                    'retake_for_attempt_id' => $attempt->id,
                ],
            ]);
        }, 3);
    }
}
