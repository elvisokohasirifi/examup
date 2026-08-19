<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamAttemptCsvController extends Controller
{
    public function __invoke(Exam $exam): StreamedResponse
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        $exam->loadMissing('attempts.accessLink');

        return response()->streamDownload(function () use ($exam): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'student_name',
                'student_email',
                'student_index_number',
                'status',
                'score',
                'score_percentage',
                'duration_seconds',
                'started_at',
                'submitted_at',
            ]);

            foreach ($exam->attempts->reject(fn ($attempt) => $attempt->accessLink?->isPreview()) as $attempt) {
                fputcsv($handle, [
                    $attempt->student_name,
                    $attempt->student_email,
                    $attempt->student_index_number,
                    $attempt->status,
                    $attempt->score,
                    $attempt->score_percentage,
                    $attempt->duration_seconds,
                    $attempt->started_at,
                    $attempt->submitted_at,
                ]);
            }

            fclose($handle);
        }, 'exam-'.$exam->id.'-results.csv');
    }
}
