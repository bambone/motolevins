<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/motorcycles', [PageController::class, 'motorcycles'])->name('motorcycles.index');
Route::get('/motorcycles/{slug}', [PageController::class, 'motorcycle'])->name('motorcycles.show');
Route::get('/prices', [PageController::class, 'prices'])->name('prices');
Route::get('/order', [PageController::class, 'order'])->name('order');
Route::get('/reviews', [PageController::class, 'reviews'])->name('reviews');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/articles', [PageController::class, 'articles'])->name('articles.index');
Route::get('/articles/{slug}', [PageController::class, 'article'])->name('articles.show');
Route::get('/delivery/anapa', [PageController::class, 'deliveryAnapa'])->name('delivery.anapa');
Route::get('/delivery/gelendzhik', [PageController::class, 'deliveryGelendzhik'])->name('delivery.gelendzhik');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
