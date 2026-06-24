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
        ->middleware('throttle:guest,30,1|auth,60,1');

    Route::post('/chatbot/stream', [ChatbotStreamController::class, 'stream'])
        ->middleware('throttle:guest,10,1|auth,20,1');
});
