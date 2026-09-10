<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\ImportMicrosoftFormsResultsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportMicrosoftFormsResultsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MicrosoftFormsImportController extends Controller
{
    public function create(): View
    {
        return view('admin.exams.import-microsoft-forms');
    }

    public function store(
        ImportMicrosoftFormsResultsRequest $request,
        ImportMicrosoftFormsResultsAction $importMicrosoftFormsResults,
    ): RedirectResponse {
        $exam = $importMicrosoftFormsResults->handle(
            $request->file('results_file'),
            $request->string('title')->trim()->toString(),
            backpack_user(),
        );

        return redirect()
            ->route('admin.exams.results', $exam)
            ->with('status', "Imported {$exam->questions()->count()} questions and {$exam->attempts()->count()} historical attempts from Microsoft Forms.");
    }
}
