<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('distributions', DistributionController::class)->except(['show']);

    Route::get('/predictions', [PredictionController::class, 'index'])->name('predictions.index');
    Route::post('/predictions/process', [PredictionController::class, 'process'])->name('predictions.process');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Tidak ada route hapus akun: sistem hanya punya satu akun Admin dan tidak ada
    // halaman registrasi, jadi menghapusnya akan mengunci aplikasi.
});

require __DIR__.'/auth.php';
