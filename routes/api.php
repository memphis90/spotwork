<?php

use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search',       [SearchController::class,     'search'])->middleware('throttle:search');
Route::get('/company-info', [CompanyInfoController::class, 'show'])->middleware('throttle:60,1');
