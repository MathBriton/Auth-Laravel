<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::patch('users/reset-password', [UserController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::patch('{user}/change-password', [UserController::class, 'changePassword']);
        Route::patch('{user}/change-status/', [UserController::class, 'changeStatus']);
        Route::patch('{user}/change-permission/', [PermissionController::class, 'update']);

        // Rotas específicas de membros
        Route::group(['prefix' => 'members'], function () {
            /* Route::get('/', [MemberController::class, 'index']);
            Route::post('/', [MemberController::class, 'store']);
            Route::put('/{id}', [MemberController::class, 'update']);
            Route::get('/{id}', [MemberController::class, 'show']); */
        });
    });
});
