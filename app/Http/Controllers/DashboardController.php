<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Product;
use Carbon\Carbon;

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

        $monthlyTotals = Distribution::selectRaw("DATE_FORMAT(distribution_date, '%Y-%m') as month, SUM(quantity) as total")
            ->where('distribution_date', '>=', $chartStart)
            ->groupBy('month')
            ->pluck('total', 'month');

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
     * Dibaca langsung dari session hasil PredictionController::process(), bukan dihitung
     * ulang dari data Produk/Distribusi saat ini. Session ini sudah dihapus oleh
     * InvalidatesPredictionCache begitu data Produk/Distribusi berubah, jadi kalau
     * sesinya kosong berarti belum ada analisis yang valid untuk data saat ini —
     * Dashboard harus tampil "belum ada analisis", bukan menghitung akurasi baru
     * dari label lama yang sudah usang.
     */
    private function buildLastAnalysisSummary(): ?array
    {
        if (! session()->has('c45_accuracy')) {
            return null;
        }

        return [
            'date' => session('c45_analyzed_at'),
            'total' => session('c45_total'),
            'accuracy' => session('c45_accuracy'),
        ];
    }
}
