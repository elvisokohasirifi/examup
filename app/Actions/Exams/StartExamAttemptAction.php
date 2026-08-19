<?php

namespace App\Actions\Exams;

use App\Models\ExamAccessLink;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;

class StartExamAttemptAction
{
    public function handle(ExamAccessLink $accessLink, array $studentData, Request $request): ExamAttempt
    {
        $exam = $accessLink->exam()->with('questions')->firstOrFail();

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'exam_access_link_id' => $accessLink->id,
            'student_name' => $studentData['student_name'],
            'student_email' => $studentData['student_email'] ?? $accessLink->email,
            'student_index_number' => $studentData['student_index_number'] ?? null,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'expires_at' => $exam->time_limit_minutes === null ? null : now()->addMinutes($exam->time_limit_minutes),
            'max_score' => $exam->questions->sum('points'),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => [
                'display_mode' => $exam->display_mode,
                'autosave_interval_seconds' => $exam->autosave_interval_seconds,
            ],
        ]);
    }
}
