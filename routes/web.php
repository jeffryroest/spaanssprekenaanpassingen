<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContentStudio\ContentController;
use App\Http\Controllers\ContentStudio\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('content-studio')
    ->name('content-studio.')
    ->middleware(['auth', 'can:content-studio.view'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/content', [ContentController::class, 'index'])->name('content.index');
        Route::get('/content/create', [ContentController::class, 'create'])->name('content.create');
        Route::post('/content', [ContentController::class, 'store'])->name('content.store');
        Route::get('/content/{contentNode}', [ContentController::class, 'show'])
            ->whereNumber('contentNode')
            ->name('content.show');
        Route::get('/content/{contentNode}/edit', [ContentController::class, 'edit'])
            ->whereNumber('contentNode')
            ->name('content.edit');
        Route::put('/content/{contentNode}', [ContentController::class, 'update'])
            ->whereNumber('contentNode')
            ->name('content.update');
        Route::delete('/content/{contentNode}', [ContentController::class, 'destroy'])
            ->whereNumber('contentNode')
            ->name('content.destroy');
    });
