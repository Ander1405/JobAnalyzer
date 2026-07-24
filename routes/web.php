<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/marketplace');

    Route::inertia('/{any}', 'App/Shell')
        ->where('any', '^(marketplace|tracking|profile)(/.*)?$')
        ->name('app.shell');
});

require __DIR__.'/auth.php';
