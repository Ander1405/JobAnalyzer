<?php

use App\Http\Controllers\Api\JobController;
use Illuminate\Support\Facades\Route;

Route::get('jobs', [JobController::class, 'index'])->name('api.jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show');
Route::post('jobs/fetch', [JobController::class, 'fetch'])->name('api.jobs.fetch');
Route::post('jobs/{job}/analyze', [JobController::class, 'analyze'])->name('api.jobs.analyze');
Route::post('jobs/{job}/publish', [JobController::class, 'publish'])->name('api.jobs.publish');
Route::patch('jobs/{job}', [JobController::class, 'update'])->name('api.jobs.update');
