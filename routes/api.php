<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\NotificationController;

// =====================================================
// AUTHENTIFICATION
// =====================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/user-by-email', [AuthController::class, 'userByEmail']);

// =====================================================
// ARTICLES PUBLICS (Portfolio) → EN DEHORS DU MIDDLEWARE
// =====================================================
Route::get('/articles/public', [ArticleController::class, 'publicIndex']);

// =====================================================
// ROUTES PROTÉGÉES PAR SANCTUM
// =====================================================
Route::middleware('auth:sanctum')->group(function () {

    // UTILISATEUR
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // PROFIL
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/update-name', [UserController::class, 'updateName']);
    Route::post('/user/update-photo', [UserController::class, 'updatePhoto']);
    Route::post('/user/update-password', [UserController::class, 'updatePassword']);

    // ARTICLES (Dashboard)
    Route::get('/articles', [ArticleController::class, 'index']); // paginée
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
    Route::post('/articles/{article}/translate', [ArticleController::class, 'translate']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    Route::post('/articles/{article}/archive', [ArticleController::class, 'archive']);
    Route::post('/articles/{article}/unarchive', [ArticleController::class, 'unarchive']);
    Route::get('/articles/stats', [ArticleController::class, 'stats']);

    // NOTIFICATIONS
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});