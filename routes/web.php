<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Jobs/Index')->name('home');
Route::inertia('/profile', 'Profile/Index')->name('profile');
