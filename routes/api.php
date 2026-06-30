<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guest\GuestSummaryController;
use App\Http\Controllers\Guest\GuestLatestController;
use App\Http\Controllers\Api\ChatbotStreamController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::withoutMiddleware('throttle:api')->group(function () {
    Route::get('/public/summary', [GuestSummaryController::class, 'index'])
        ->middleware(['throttle:public-summary']);

    Route::get('/public/latest', [GuestLatestController::class, 'index'])
        ->middleware(['throttle:public-summary']);

    Route::post('/chatbot/message', [\App\Http\Controllers\Api\ChatbotController::class, 'chat'])
        ->middleware('throttle:50,1');

    Route::post('/chatbot/stream', [ChatbotStreamController::class, 'stream'])
        ->middleware('throttle:50,1');

    Route::get('/autocomplete/data', [\App\Http\Controllers\Api\AutocompleteController::class, 'data'])
        ->middleware(['auth', 'throttle:60,1']);
});
