<?php

use App\Http\Controllers\Admin\ExamAccessLinkCrudController;
use App\Http\Controllers\Admin\ExamAnalyticsController;
use App\Http\Controllers\Admin\ExamAttemptCrudController;
use App\Http\Controllers\Admin\ExamCrudController;
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
    Route::crud('exam-access-link', ExamAccessLinkCrudController::class);
    Route::crud('exam-attempt', ExamAttemptCrudController::class);
    Route::get('exams/{exam}/preview', [ExamCrudController::class, 'preview'])->name('admin.exams.preview');
    Route::get('exams/{exam}/analytics', ExamAnalyticsController::class)->name('admin.exams.analytics');
    Route::get('exams/{exam}/results.csv', ExamAttemptCsvController::class)->name('admin.exams.csv');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
