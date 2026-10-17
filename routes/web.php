<?php

use App\Http\Controllers\Admin\LabAnalyticsController;
use App\Http\Controllers\Admin\ProgressController;
use App\Http\Controllers\Admin\ProgressOverrideController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\FlagController;
use App\Http\Controllers\HintController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LabInstanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [GoogleController::class, 'redirect'])->name('login');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [GoogleController::class, 'logout'])->name('logout');

    Route::get('/labs', [LabController::class, 'index'])->name('labs.index');
    Route::get('/labs/{lab:slug}', [LabController::class, 'show'])->name('labs.show');
    Route::get('/labs/{lab:slug}/open', [LabController::class, 'open'])->name('labs.open');
    Route::post('/labs/{lab:slug}/launch', [LabInstanceController::class, 'launch'])->name('labs.launch');
    Route::get('/labs/{lab:slug}/status', [LabInstanceController::class, 'status'])->name('labs.status');
    Route::get('/labs/{lab:slug}/hints', [HintController::class, 'index'])->name('labs.hints');
    Route::post('/labs/{lab:slug}/flag', [FlagController::class, 'store'])
        ->middleware('throttle:flag-check')
        ->name('labs.flag');

    Route::middleware('can:admin')->prefix('admin')->withoutScopedBindings()->group(function () {
        Route::get('/', [ProgressController::class, 'index'])->name('admin.progress');
        Route::get('/labs', [LabAnalyticsController::class, 'index'])->name('admin.labs');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::post('/users/{user}/labs/{lab:slug}/reset', [ProgressOverrideController::class, 'reset'])
            ->name('admin.progress.reset');
        Route::post('/users/{user}/labs/{lab:slug}/complete', [ProgressOverrideController::class, 'complete'])
            ->name('admin.progress.complete');
    });
});
