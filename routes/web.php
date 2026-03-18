<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/offline', 'offline')->name('offline');
Route::post('/api/bookings', [BookingController::class, 'store'])->name('api.bookings.store');
