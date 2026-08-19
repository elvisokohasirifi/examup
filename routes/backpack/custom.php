<?php

use App\Http\Controllers\Admin\ExamAccessController;
use App\Http\Controllers\Admin\ExamAttemptCsvController;
use App\Http\Controllers\Admin\ExamCrudController;
use App\Http\Controllers\Admin\ExamResultsController;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('exam', ExamCrudController::class);
    Route::get('exams/{exam}/access', [ExamAccessController::class, 'show'])->name('admin.exams.access');
    Route::post('exams/{exam}/access', [ExamAccessController::class, 'store'])->name('admin.exams.access.store');
    Route::get('exams/{exam}/preview', [ExamCrudController::class, 'preview'])->name('admin.exams.preview');
    Route::get('exams/{exam}/results', ExamResultsController::class)->name('admin.exams.results');
    Route::get('exams/{exam}/results.csv', ExamAttemptCsvController::class)->name('admin.exams.csv');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
