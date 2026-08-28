<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\RegradeExamAttemptsAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;

class RegradeExamAttemptsController extends Controller
{
    public function __invoke(Exam $exam, RegradeExamAttemptsAction $regradeExamAttempts): RedirectResponse
    {
        abort_unless(backpack_user()->can('update', $exam), 403);

        $regradedAttempts = $regradeExamAttempts->handle($exam);

        return to_route('admin.exams.results', $exam)
            ->with('success', "Regraded {$regradedAttempts} completed attempt(s).");
    }
}
