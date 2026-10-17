<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
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

    // Akun hanya bisa dibuat lewat menu Pengguna oleh pengguna yang sudah login.
    // Tidak ada halaman registrasi publik, dan semua akun setara (tanpa sistem role).
    Route::resource('users', UserController::class)->except(['show']);

    Route::get('/questionnaire', [QuestionnaireController::class, 'index'])->name('questionnaire.index');
    Route::post('/questionnaire', [QuestionnaireController::class, 'store'])->name('questionnaire.store');
    Route::get('/questionnaire/results', [QuestionnaireController::class, 'results'])->name('questionnaire.results');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Tidak ada route hapus akun dari halaman Profil: penghapusan akun dilakukan lewat
    // menu Pengguna, yang mencegah pengguna menghapus akunnya sendiri.
});

require __DIR__.'/auth.php';
