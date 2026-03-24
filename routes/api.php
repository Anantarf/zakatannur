<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guest\GuestSummaryController;
use App\Http\Controllers\Guest\GuestLatestController;

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

Route::get('/public/summary', [GuestSummaryController::class, 'index'])
    ->middleware(['throttle:public-summary'])
    ->withoutMiddleware('throttle:api');

Route::get('/public/latest', [GuestLatestController::class, 'index'])
    ->middleware(['throttle:public-summary'])
    ->withoutMiddleware('throttle:api');
