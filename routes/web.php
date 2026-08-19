<?php

use App\Livewire\TakeExam;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/exam/{publicKey}/{accessToken}', TakeExam::class)->name('exam.take');
