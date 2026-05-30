<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;


Route::get('/login', [UserAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserAuthController::class, 'login'])->name('login.submit');
Route::get('/register', [UserAuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [UserAuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');

Route::get('/', [AdminAuthController::class, 'showLoginForm']);

Route::group(['middleware' => ['auth:web']], function () {
    
});

