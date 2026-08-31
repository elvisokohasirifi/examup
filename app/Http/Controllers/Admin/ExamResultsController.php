<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\BuildExamStatisticsAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ExamResultsController extends Controller
{
    public function __invoke(Request $request, Exam $exam, BuildExamStatisticsAction $buildExamStatistics): View
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        $studentNameSearch = trim((string) $request->query('student_name', ''));

        $attempts = $exam->attempts()
            ->current()
            ->with('accessLink')
            ->withCount('suspiciousActivities')
            ->when($studentNameSearch !== '', fn ($query) => $query->where('student_name', 'like', "%{$studentNameSearch}%"))
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
            'studentNameSearch' => $studentNameSearch,
        ]);
    }
}
