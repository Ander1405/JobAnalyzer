<?php

use App\Http\Controllers\Api\AiSettingsController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\JobCvPdfController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\NavController;
use App\Http\Controllers\Api\ProfileAtsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfileReviewController;
use App\Http\Controllers\Api\ProfileTailorController;
use App\Http\Controllers\Api\ProfileVariantController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('jobs', [JobController::class, 'index'])->name('api.jobs.index');
    Route::get('jobs/sources', [JobController::class, 'sources'])->name('api.jobs.sources');
    Route::get('jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show');
    Route::post('jobs/fetch', [JobController::class, 'fetch'])->name('api.jobs.fetch');
    Route::post('jobs/{job}/analyze', [JobController::class, 'analyze'])->name('api.jobs.analyze');
    Route::post('jobs/{job}/publish', [JobController::class, 'publish'])->name('api.jobs.publish');
    Route::patch('jobs/{job}', [JobController::class, 'update'])->name('api.jobs.update');
    Route::get('jobs/{job}/cv/pdf', [JobCvPdfController::class, 'show'])->name('api.jobs.cv.pdf');

    Route::get('ai/settings', [AiSettingsController::class, 'index'])->name('api.ai.settings.index');
    Route::put('ai/settings', [AiSettingsController::class, 'update'])->name('api.ai.settings.update');
    Route::get('ai/providers', [AiSettingsController::class, 'providers'])->name('api.ai.providers');
    Route::get('ai/providers/{provider}/models', [AiSettingsController::class, 'models'])->name('api.ai.providers.models');

    Route::get('profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::post('profile/import', [ProfileController::class, 'import'])->name('api.profile.import');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password.update');

    Route::get('profiles', [ProfileVariantController::class, 'index'])->name('api.profiles.index');
    Route::post('profiles', [ProfileVariantController::class, 'store'])->name('api.profiles.store');
    Route::get('profile/{profile}', [ProfileVariantController::class, 'show'])->name('api.profile.variant.show');
    Route::put('profile/{profile}', [ProfileVariantController::class, 'update'])->name('api.profile.variant.update');
    Route::post('profile/{profile}/sync', [ProfileVariantController::class, 'sync'])->name('api.profile.variant.sync');
    Route::post('profile/{profile}/activate', [ProfileVariantController::class, 'activate'])->name('api.profile.variant.activate');

    Route::post('profile/{profile}/review', [ProfileReviewController::class, 'review'])->name('api.profile.review');
    Route::post('profile/{profile}/suggestions/apply', [ProfileReviewController::class, 'apply'])->name('api.profile.suggestions.apply');

    Route::post('profile/tailor', [ProfileTailorController::class, 'preview'])->name('api.profile.tailor.preview');
    Route::get('profile/tailor/{requestId}', [ProfileTailorController::class, 'status'])->name('api.profile.tailor.status');
    Route::post('profile/tailor/confirm', [ProfileTailorController::class, 'confirm'])->name('api.profile.tailor.confirm');

    Route::post('profile/ats', [ProfileAtsController::class, 'analyze'])->name('api.profile.ats.analyze');
    Route::post('profile/ats/confirm', [ProfileAtsController::class, 'confirm'])->name('api.profile.ats.confirm');

    Route::get('nav/badges', [NavController::class, 'badges'])->name('api.nav.badges');

    Route::get('marketplace', [MarketplaceController::class, 'index'])->name('api.marketplace.index');
    Route::post('marketplace/track-bulk', [MarketplaceController::class, 'trackBulk'])->name('api.marketplace.track-bulk');
    Route::post('marketplace/analyze-pending', [MarketplaceController::class, 'analyzePending'])->name('api.marketplace.analyze-pending');
    Route::post('marketplace/{job}/track', [MarketplaceController::class, 'track'])->name('api.marketplace.track');

    Route::get('tracking', [TrackingController::class, 'index'])->name('api.tracking.index');
    Route::get('tracking/{trackedJob}', [TrackingController::class, 'show'])->name('api.tracking.show');
    Route::patch('tracking/{trackedJob}', [TrackingController::class, 'update'])->name('api.tracking.update');
    Route::post('tracking/{trackedJob}/comments', [TrackingController::class, 'storeComment'])->name('api.tracking.comments.store');
    Route::delete('tracking/{trackedJob}', [TrackingController::class, 'destroy'])->name('api.tracking.destroy');
});
