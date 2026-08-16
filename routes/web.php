<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContentStudio\ContentController;
use App\Http\Controllers\ContentStudio\ContentReleaseController;
use App\Http\Controllers\ContentStudio\ContentReleaseItemController;
use App\Http\Controllers\ContentStudio\ContentReleasePublicationController;
use App\Http\Controllers\ContentStudio\ContentReviewController;
use App\Http\Controllers\ContentStudio\DashboardController;
use App\Http\Controllers\ContentStudio\ReviewQueueController;
use App\Http\Controllers\Game\SpeechTranscriptionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/privacybeleid', 'privacy')->name('privacy');
Route::view('/spelen/madrid', 'game.madrid')->name('game.madrid');
Route::view('/spelen/madrid/la-panaderia', 'game.panaderia')->name('game.madrid.panaderia');
Route::post('/spelen/madrid/la-panaderia/transcriptie', SpeechTranscriptionController::class)
    ->middleware('throttle:speech-transcriptions')
    ->name('game.madrid.panaderia.transcription');

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

        Route::get('/reviews', ReviewQueueController::class)->name('reviews.index');
        Route::post('/reviews/{contentNode}/decision', [ContentReviewController::class, 'decide'])
            ->whereNumber('contentNode')
            ->name('reviews.decide');

        Route::get('/releases', [ContentReleaseController::class, 'index'])->name('releases.index');
        Route::get('/releases/create', [ContentReleaseController::class, 'create'])->name('releases.create');
        Route::post('/releases', [ContentReleaseController::class, 'store'])->name('releases.store');
        Route::get('/releases/{contentRelease}', [ContentReleaseController::class, 'show'])
            ->whereNumber('contentRelease')
            ->name('releases.show');
        Route::post('/releases/{contentRelease}/items', [ContentReleaseItemController::class, 'store'])
            ->whereNumber('contentRelease')
            ->name('releases.items.store');
        Route::delete('/releases/{contentRelease}/items/{contentReleaseItem}', [ContentReleaseItemController::class, 'destroy'])
            ->whereNumber('contentRelease')
            ->whereNumber('contentReleaseItem')
            ->name('releases.items.destroy');
        Route::post('/releases/{contentRelease}/publish', [ContentReleasePublicationController::class, 'publish'])
            ->whereNumber('contentRelease')
            ->name('releases.publish');
        Route::post('/releases/{contentRelease}/cancel', [ContentReleasePublicationController::class, 'cancel'])
            ->whereNumber('contentRelease')
            ->name('releases.cancel');

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
        Route::post('/content/{contentNode}/submit-review', [ContentReviewController::class, 'submit'])
            ->whereNumber('contentNode')
            ->name('content.submit-review');
        Route::delete('/content/{contentNode}', [ContentController::class, 'destroy'])
            ->whereNumber('contentNode')
            ->name('content.destroy');
    });
