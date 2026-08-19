<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\BuildExamStatisticsAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Contracts\View\View;

class ExamAnalyticsController extends Controller
{
    public function __invoke(Exam $exam, BuildExamStatisticsAction $buildExamStatistics): View
    {
        abort_unless(backpack_user()->can('view', $exam), 403);

        return view('admin.exams.analytics', [
            'exam' => $exam->loadMissing('questions.options'),
            'stats' => $buildExamStatistics->handle($exam),
        ]);
    }
}
