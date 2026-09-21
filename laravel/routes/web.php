<?php

use App\Http\Controllers\JobBrowseController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/viec-lam');
Route::get('/viec-lam', [JobBrowseController::class, 'index'])->name('jobs.index');
Route::get('/migration-status', [JobBrowseController::class, 'status'])->name('migration.status');
