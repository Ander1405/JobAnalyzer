<?php

use App\Http\Controllers\Api\AiSettingsController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('jobs', [JobController::class, 'index'])->name('api.jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show');
Route::post('jobs/fetch', [JobController::class, 'fetch'])->name('api.jobs.fetch');
Route::post('jobs/{job}/analyze', [JobController::class, 'analyze'])->name('api.jobs.analyze');
Route::post('jobs/{job}/publish', [JobController::class, 'publish'])->name('api.jobs.publish');
Route::patch('jobs/{job}', [JobController::class, 'update'])->name('api.jobs.update');

Route::get('ai/settings', [AiSettingsController::class, 'index'])->name('api.ai.settings.index');
Route::put('ai/settings', [AiSettingsController::class, 'update'])->name('api.ai.settings.update');
Route::get('ai/providers', [AiSettingsController::class, 'providers'])->name('api.ai.providers');
Route::get('ai/providers/{provider}/models', [AiSettingsController::class, 'models'])->name('api.ai.providers.models');

Route::get('profile', [ProfileController::class, 'show'])->name('api.profile.show');
Route::post('profile/import', [ProfileController::class, 'import'])->name('api.profile.import');
