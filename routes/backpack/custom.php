<?php

use App\Http\Controllers\Admin\Auth\GoogleLoginController;
use App\Http\Controllers\Admin\ExamAccessController;
use App\Http\Controllers\Admin\ExamAttemptCsvController;
use App\Http\Controllers\Admin\ExamAttemptReviewController;
use App\Http\Controllers\Admin\ExamCrudController;
use App\Http\Controllers\Admin\ExamResultsController;
use App\Http\Controllers\Admin\RegradeExamAttemptsController;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => (array) config('backpack.base.web_middleware', 'web'),
], function (): void {
    Route::get('auth/google/redirect', [GoogleLoginController::class, 'redirect'])->name('admin.auth.google.redirect');
    Route::get('auth/google/callback', [GoogleLoginController::class, 'callback'])->name('admin.auth.google.callback');
});

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('exam/questions/sample.txt', [ExamCrudController::class, 'downloadQuestionImportSample'])
        ->name('admin.exams.questions.sample');
    Route::crud('exam', ExamCrudController::class);
    Route::get('exams/{exam}/access', [ExamAccessController::class, 'show'])->name('admin.exams.access');
    Route::post('exams/{exam}/access', [ExamAccessController::class, 'store'])->name('admin.exams.access.store');
    Route::delete('exams/{exam}/access/{accessLink}', [ExamAccessController::class, 'destroy'])
        ->scopeBindings()
        ->name('admin.exams.access.destroy');
    Route::get('exams/{exam}/preview', [ExamCrudController::class, 'preview'])->name('admin.exams.preview');
    Route::get('exams/{exam}/results', ExamResultsController::class)->name('admin.exams.results');
    Route::post('exams/{exam}/results/regrade', RegradeExamAttemptsController::class)->name('admin.exams.regrade');
    Route::get('exams/{exam}/attempts/{attempt}', ExamAttemptReviewController::class)
        ->scopeBindings()
        ->name('admin.exams.attempts.show');
    Route::get('exams/{exam}/results.csv', ExamAttemptCsvController::class)->name('admin.exams.csv');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
