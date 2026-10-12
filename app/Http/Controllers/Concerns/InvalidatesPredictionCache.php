<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Cache;

trait InvalidatesPredictionCache
{
    /**
     * Hapus ringkasan analisis C4.5 yang tersimpan di cache karena data Produk/Distribusi
     * berubah, supaya pengguna tidak melihat Decision Tree/akurasi yang sudah usang.
     */
    private function forgetPredictionCache(): void
    {
        Cache::forget(PredictionController::ANALYSIS_CACHE_KEY);
    }
}
