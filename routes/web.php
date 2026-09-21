<?php

use Illuminate\Support\Facades\Route;


// =====================================================
// PAGE ACCUEIL
// =====================================================

Route::get('/', function () {
    return [
        'Laravel' => app()->version()
    ];
});


// =====================================================
// FORMULAIRES BLADE
// =====================================================

// Tu peux supprimer ces routes si tu n'utilises plus
// les formulaires Blade Laravel.

// Route::view('/login-form', 'login-form');
// Route::view('/register-form', 'register-form');
// Route::view('/forgot-password', 'forgot-password');


// =====================================================
// DASHBOARD LARAVEL
// =====================================================

// Si ton dashboard est React, tu n'as PAS besoin
// de cette route Laravel.

// Route::get('/dashboard', [
//     DashboardController::class,
//     'index'
// ])->name('dashboard')->middleware('auth');
