<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:search');
Route::get('/jobs',   [SearchController::class, 'jobs'])->middleware('throttle:jobs');
