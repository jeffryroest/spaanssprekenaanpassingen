<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContentStudio\ContentController;
use App\Http\Controllers\ContentStudio\ContentReleaseController;
use App\Http\Controllers\ContentStudio\ContentReleaseItemController;
use App\Http\Controllers\ContentStudio\ContentReleasePublicationController;
use App\Http\Controllers\ContentStudio\ContentReviewController;
use App\Http\Controllers\ContentStudio\DashboardController;
use App\Http\Controllers\ContentStudio\ReviewQueueController;
use App\Http\Controllers\Game\CompleteHealthMissionController;
use App\Http\Controllers\Game\CompletePanaderiaMissionController;
use App\Http\Controllers\Game\CompleteRestaurantMissionController;
use App\Http\Controllers\Game\CompleteTaxiMissionController;
use App\Http\Controllers\Game\EntitledConversationController;
use App\Http\Controllers\Game\SpeechTranscriptionController;
use App\Http\Controllers\Game\TurnFeedbackController;
use App\Http\Controllers\PlayerProgressController;
use App\Http\Controllers\TrialWeekController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/privacybeleid', 'privacy')->name('privacy');
Route::view('/spelen/madrid', 'game.madrid')->name('game.madrid');
Route::view('/spelen/madrid/la-panaderia', 'game.panaderia')->name('game.madrid.panaderia');
Route::post('/spelen/madrid/la-panaderia/transcriptie', SpeechTranscriptionController::class)
    ->middleware('throttle:speech-transcriptions')
    ->name('game.madrid.panaderia.transcription');
Route::post('/spelen/madrid/la-panaderia/feedback', TurnFeedbackController::class)
    ->middleware('throttle:turn-feedback')
    ->name('game.madrid.panaderia.feedback');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/proefweek', [TrialWeekController::class, 'show'])
        ->name('trial-week.show');
    Route::get('/spelen/proefweek/status', [TrialWeekController::class, 'json'])
        ->name('game.trial-week.status');
    Route::get('/mijn-voortgang', [PlayerProgressController::class, 'show'])
        ->name('player.progress');
    Route::get('/spelen/voortgang', [PlayerProgressController::class, 'json'])
        ->name('game.progress');
    Route::post('/spelen/madrid/la-panaderia/voltooien', CompletePanaderiaMissionController::class)
        ->middleware('throttle:mission-completions')
        ->name('game.madrid.panaderia.complete');

    Route::view('/spelen/madrid/taxi', 'game.taxi')
        ->middleware('entitled:trial_week')
        ->name('game.madrid.taxi');
    Route::get('/spelen/madrid/taxi/content', EntitledConversationController::class)
        ->defaults('scenarioSlug', 'taxi-diego')
        ->defaults('requiredEntitlement', 'trial_week')
        ->middleware(['entitled:trial_week', 'throttle:120,1'])
        ->name('game.madrid.taxi.content');
    Route::post('/spelen/madrid/taxi/transcriptie', SpeechTranscriptionController::class)
        ->middleware(['entitled:trial_week', 'throttle:speech-transcriptions'])
        ->name('game.madrid.taxi.transcription');
    Route::post('/spelen/madrid/taxi/feedback', TurnFeedbackController::class)
        ->middleware(['entitled:trial_week', 'throttle:turn-feedback'])
        ->name('game.madrid.taxi.feedback');
    Route::post('/spelen/madrid/taxi/voltooien', CompleteTaxiMissionController::class)
        ->middleware(['entitled:trial_week', 'throttle:mission-completions'])
        ->name('game.madrid.taxi.complete');

    Route::view('/spelen/madrid/restaurant', 'game.restaurant')
        ->middleware('entitled:trial_week')
        ->name('game.madrid.restaurant');
    Route::get('/spelen/madrid/restaurant/content', EntitledConversationController::class)
        ->defaults('scenarioSlug', 'restaurant-el-reloj')
        ->defaults('requiredEntitlement', 'trial_week')
        ->middleware(['entitled:trial_week', 'throttle:120,1'])
        ->name('game.madrid.restaurant.content');
    Route::post('/spelen/madrid/restaurant/transcriptie', SpeechTranscriptionController::class)
        ->middleware(['entitled:trial_week', 'throttle:speech-transcriptions'])
        ->name('game.madrid.restaurant.transcription');
    Route::post('/spelen/madrid/restaurant/feedback', TurnFeedbackController::class)
        ->middleware(['entitled:trial_week', 'throttle:turn-feedback'])
        ->name('game.madrid.restaurant.feedback');
    Route::post('/spelen/madrid/restaurant/voltooien', CompleteRestaurantMissionController::class)
        ->middleware(['entitled:trial_week', 'throttle:mission-completions'])
        ->name('game.madrid.restaurant.complete');

    Route::view('/spelen/madrid/gezondheid', 'game.health')
        ->middleware('entitled:trial_week')
        ->name('game.madrid.health');
    Route::get('/spelen/madrid/gezondheid/content', EntitledConversationController::class)
        ->defaults('scenarioSlug', 'consulta-elena')
        ->defaults('requiredEntitlement', 'trial_week')
        ->middleware(['entitled:trial_week', 'throttle:120,1'])
        ->name('game.madrid.health.content');
    Route::post('/spelen/madrid/gezondheid/transcriptie', SpeechTranscriptionController::class)
        ->middleware(['entitled:trial_week', 'throttle:speech-transcriptions'])
        ->name('game.madrid.health.transcription');
    Route::post('/spelen/madrid/gezondheid/feedback', TurnFeedbackController::class)
        ->middleware(['entitled:trial_week', 'throttle:turn-feedback'])
        ->name('game.madrid.health.feedback');
    Route::post('/spelen/madrid/gezondheid/voltooien', CompleteHealthMissionController::class)
        ->middleware(['entitled:trial_week', 'throttle:mission-completions'])
        ->name('game.madrid.health.complete');
});

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
        Route::post('/content/{contentNode}/withdraw-review', [ContentReviewController::class, 'withdraw'])
            ->whereNumber('contentNode')
            ->name('content.withdraw-review');
        Route::delete('/content/{contentNode}', [ContentController::class, 'destroy'])
            ->whereNumber('contentNode')
            ->name('content.destroy');
    });
