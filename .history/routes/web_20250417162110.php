<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/filieres', [FiliereController::class, 'index'])->name('filieres.index');