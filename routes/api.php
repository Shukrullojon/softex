<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [\App\Http\Controllers\Apis\AuthenticationController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Apis\AuthenticationController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // avatar
    Route::post('/avatar', [\App\Http\Controllers\Apis\AvatarController::class, 'createOrUpdateAvatar']);
    Route::delete('/avatar', [\App\Http\Controllers\Apis\AvatarController::class, 'deleteAvatar']);
    // category
    Route::get('/categories', [\App\Http\Controllers\Apis\CategoryController::class, 'get']);
    Route::post('/categories', [\App\Http\Controllers\Apis\CategoryController::class, 'create']);
    Route::put('/categories/{id}', [\App\Http\Controllers\Apis\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\Apis\CategoryController::class, 'delete']);
    // transaction
    Route::get('/transactions', [\App\Http\Controllers\Apis\TransactionController::class, 'index']);
    Route::post('/transactions', [\App\Http\Controllers\Apis\TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [\App\Http\Controllers\Apis\TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [\App\Http\Controllers\Apis\TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [\App\Http\Controllers\Apis\TransactionController::class, 'destroy']);
    Route::get('/transactions/get/statistics', [\App\Http\Controllers\Apis\TransactionController::class, 'getStatistics']);
    Route::get('/transactions/export/excel', [\App\Http\Controllers\Apis\TransactionController::class, 'exportTransactions']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

