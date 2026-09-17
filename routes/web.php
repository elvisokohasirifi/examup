<?php

use App\Http\Controllers\OrganizationRegistrationController;
use App\Livewire\TakeExam;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/register', [OrganizationRegistrationController::class, 'create'])->name('organization.register');
Route::post('/register', [OrganizationRegistrationController::class, 'store'])->name('organization.register.store');
Route::livewire('/exam/{publicKey}/{accessToken}', TakeExam::class)->name('exam.take');
