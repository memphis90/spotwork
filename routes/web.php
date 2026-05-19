<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Home'));

Route::get('/dashboard', fn() => Inertia::render('Dashboard'))
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/saved', [SavedController::class, 'index'])->name('saved.index');
    Route::delete('/saved/companies/{savedCompany}', [SavedController::class, 'destroyCompany'])->name('saved.company.destroy');
    Route::delete('/saved/jobs/{savedJob}', [SavedController::class, 'destroyJob'])->name('saved.job.destroy');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings',             [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings/message',   [SettingsController::class, 'updateMessage'])->name('settings.message');
    Route::post('/settings/cv',         [SettingsController::class, 'uploadCv'])->name('settings.cv.upload');
    Route::delete('/settings/cv',       [SettingsController::class, 'deleteCv'])->name('settings.cv.delete');
    Route::get('/settings/cv/download', [SettingsController::class, 'downloadCv'])->name('settings.cv.download');
});

require __DIR__.'/auth.php';