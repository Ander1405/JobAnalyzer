<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/marketplace');
    }

    return Inertia::render('Marketing/Landing');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('/{any}', 'App/Shell')
        ->where('any', '^(marketplace|tracking|profile|admin)(/.*)?$')
        ->name('app.shell');
});

require __DIR__.'/auth.php';
