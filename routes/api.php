<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/subscription/create', [SubscriptionController::class, 'createSubscription']);
    Route::put('/subscription/update', [SubscriptionController::class, 'updateSubscription']);
     Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription']);
    Route::post('/subscription/resume', [SubscriptionController::class, 'resumeSubscription']);
});
