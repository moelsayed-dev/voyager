<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\NotificationController;

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

Route::middleware('auth:api')->get('/user', function (Request $request)
{
    return $request->user();
});

// User routes
Route::prefix('auth')->group(function ()
{
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('profile/{user}', [AuthController::class, 'profile']);

    Route::group(['middleware' => ['auth:sanctum', 'apiGuestUser']], function () {
        Route::post('update', [AuthController::class, 'update']);
        Route::post('getUser', [AuthController::class, 'getUser']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('hardLogout', [AuthController::class, 'logoutFromAllDevices']);
    });
});

// Trips' routes.
Route::get('trips', [TripController::class, 'index']);
Route::get('trips/{trip}', [TripController::class, 'show']);
Route::post('trips/search', [TripController::class, 'search']);

Route::group(['middleware' => ['auth:sanctum']], function ()
{
    Route::post('trips', [TripController::class, 'store']);
    Route::put('trips/{trip}', [TripController::class, 'update']);
    Route::delete('trips/{trip}', [TripController::class, 'destroy']);
});

// Offers' routes
Route::group(['middleware' => ['auth:sanctum', 'apiGuestUser']], function () {
    Route::get('offers', [OfferController::class, 'show']);
    Route::post('offers', [OfferController::class, 'store']);
    Route::put('offers/{offer}', [OfferController::class, 'update']);
    Route::delete('offers/{offer}', [OfferController::class, 'destroy']);
    Route::post('offers/respond/{offer}', [OfferController::class, 'respondToOffer']);
});

// Rating routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('rates', [RateController::class, 'store']);
});
Route::get('rates/{user}', [RateController::class, 'user_avg_rating']);

// Notifications routes.
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('notifications', [NotificationController::class, 'show']);
    Route::get('notifications/markRead', [NotificationController::class, 'markAsRead']);
});
