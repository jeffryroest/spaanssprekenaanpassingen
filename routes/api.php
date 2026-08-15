<?php

use App\Http\Controllers\Api\V1\PublishedContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:120,1')
    ->group(function (): void {
        Route::get('/worlds', [PublishedContentController::class, 'worlds'])->name('worlds.index');
        Route::get('/worlds/{slug}', [PublishedContentController::class, 'world'])
            ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->name('worlds.show');

        Route::get('/locations', [PublishedContentController::class, 'locations'])->name('locations.index');
        Route::get('/locations/{slug}', [PublishedContentController::class, 'location'])
            ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->name('locations.show');

        Route::get('/missions', [PublishedContentController::class, 'missions'])->name('missions.index');
        Route::get('/missions/{slug}', [PublishedContentController::class, 'mission'])
            ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->name('missions.show');

        Route::get('/conversations', [PublishedContentController::class, 'conversations'])->name('conversations.index');
        Route::get('/conversations/{slug}', [PublishedContentController::class, 'conversation'])
            ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->name('conversations.show');
    });
