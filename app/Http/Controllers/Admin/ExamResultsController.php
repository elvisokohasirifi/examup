<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\BuildExamStatisticsAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Contracts\View\View;

class ExamResultsController extends Controller
{
    public function __invoke(Exam $exam, BuildExamStatisticsAction $buildExamStatistics): View
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        $attempts = $exam->attempts()
            ->current()
            ->with('accessLink')
            ->withCount('suspiciousActivities')
            ->whereHas('accessLink', fn ($query) => $query->where(function ($nestedQuery) {
                $nestedQuery->whereNull('meta')
                    ->orWhere('meta->is_preview', false)
                    ->orWhereNull('meta->is_preview');
            }))
            ->latest('submitted_at')
            ->latest('created_at')
            ->get();

        return view('admin.exams.results', [
            'exam' => $exam->loadMissing('questions.options'),
            'stats' => $buildExamStatistics->handle($exam),
            'attempts' => $attempts,
        ]);
    }
}
