<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiController::class, 'login']);
Route::post('/register', [ApiController::class, 'register']);

Route::middleware('auth:web')->group(function () {
    Route::get('/user', function () {
        return auth()->user()->load('rolePermissions');
    });
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
    Route::get('/applications', [ApiController::class, 'applications']);
    Route::get('/applications/{application}', [ApiController::class, 'showApplication']);
    Route::post('/applications', [ApiController::class, 'storeApplication']);

    Route::get('/vehicles', [ApiController::class, 'vehicles']);
    Route::get('/vehicles/{vehicle}', [ApiController::class, 'showVehicle']);

    Route::get('/permits', [ApiController::class, 'permits']);
    Route::get('/permits/{permit}', [ApiController::class, 'showPermit']);

    Route::get('/franchises', [ApiController::class, 'franchises']);
    Route::get('/franchises/{franchise}', [ApiController::class, 'showFranchise']);

    Route::get('/renewals', [ApiController::class, 'renewals']);

    Route::get('/violations', [ApiController::class, 'violations']);
    Route::get('/violations/{violation}', [ApiController::class, 'showViolation']);

    Route::get('/reports', [ApiController::class, 'reports']);
    Route::post('/reports', [ApiController::class, 'storeReport']);

    Route::get('/notifications', [ApiController::class, 'notifications']);
    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        return response()->json(['message' => 'Logged out']);
    });
});
