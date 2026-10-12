<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Tampilkan ringkasan dashboard.
     */
    public function index()
    {
        $totalProducts = Product::count();
        $totalDistributions = Distribution::count();
        $todayDistributions = Distribution::whereDate('distribution_date', today())->count();

        $earliestDistributionDate = Distribution::min('distribution_date');

        $chartStart = $earliestDistributionDate
            ? Carbon::parse($earliestDistributionDate)->startOfMonth()
            : now()->startOfMonth();

        // Dikelompokkan per bulan di PHP (bukan DATE_FORMAT di SQL) supaya query tidak
        // bergantung pada fungsi khusus MySQL dan tetap jalan di SQLite saat testing.
        $monthlyTotals = Distribution::where('distribution_date', '>=', $chartStart)
            ->get(['distribution_date', 'quantity'])
            ->groupBy(fn (Distribution $distribution) => $distribution->distribution_date->format('Y-m'))
            ->map(fn ($distributions) => $distributions->sum('quantity'));

        $chartData = collect();
        $cursor = $chartStart->copy();

        while ($cursor->lte(now())) {
            $chartData->push([
                'label' => $cursor->translatedFormat('M Y'),
                'total' => (int) ($monthlyTotals[$cursor->format('Y-m')] ?? 0),
            ]);

            $cursor->addMonth();
        }

        $topProducts = Product::withSum('distributions', 'quantity')
            ->orderByDesc('distributions_sum_quantity')
            ->take(5)
            ->get();

        $latestPredictions = Product::whereNotNull('predicted_label')
            ->orderByDesc('last_classified_at')
            ->take(5)
            ->get();

        $distributionTrend = $this->calculateMonthlyDistributionTrend();

        $averageStock = round(Product::avg('stock') ?? 0);

        $restockRecommendations = Product::where('predicted_label', 'Laris')
            ->where('stock', '<', $averageStock)
            ->orderBy('stock')
            ->take(5)
            ->get();

        $lastAnalysis = $this->buildLastAnalysisSummary();

        return view('dashboard', compact(
            'totalProducts',
            'totalDistributions',
            'todayDistributions',
            'chartData',
            'topProducts',
            'latestPredictions',
            'distributionTrend',
            'restockRecommendations',
            'lastAnalysis'
        ));
    }

    /**
     * Hitung persentase perubahan jumlah transaksi distribusi bulan ini
     * dibanding bulan sebelumnya.
     */
    private function calculateMonthlyDistributionTrend(): ?float
    {
        $currentMonthCount = Distribution::whereYear('distribution_date', now()->year)
            ->whereMonth('distribution_date', now()->month)
            ->count();

        $previousMonth = now()->subMonthNoOverflow();

        $previousMonthCount = Distribution::whereYear('distribution_date', $previousMonth->year)
            ->whereMonth('distribution_date', $previousMonth->month)
            ->count();

        if ($previousMonthCount > 0) {
            return round((($currentMonthCount - $previousMonthCount) / $previousMonthCount) * 100, 1);
        }

        return $currentMonthCount > 0 ? 100.0 : null;
    }

    /**
     * Ringkasan analisis C4.5 terakhir: tanggal, jumlah produk, dan akurasi.
     *
     * Dibaca langsung dari cache hasil PredictionController::process(), bukan dihitung
     * ulang dari data Produk/Distribusi saat ini. Cache ini sudah dihapus oleh
     * InvalidatesPredictionCache begitu data Produk/Distribusi berubah, jadi kalau
     * kosong berarti belum ada analisis yang valid untuk data saat ini —
     * Dashboard harus tampil "belum ada analisis", bukan menghitung akurasi baru
     * dari label lama yang sudah usang.
     */
    private function buildLastAnalysisSummary(): ?array
    {
        $analysis = Cache::get(PredictionController::ANALYSIS_CACHE_KEY);

        if (! $analysis) {
            return null;
        }

        return [
            'date' => Carbon::parse($analysis['analyzed_at']),
            'total' => $analysis['total'],
            'accuracy' => $analysis['accuracy'],
        ];
    }
}
