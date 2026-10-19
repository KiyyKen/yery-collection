<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\C45Classifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class PredictionController extends Controller
{
    /**
     * Key cache tempat ringkasan analisis C4.5 terakhir disimpan (pohon, akurasi, threshold).
     *
     * Disimpan di cache aplikasi (tabel `cache`), bukan session, supaya tetap ada setelah
     * logout/login ulang — sama seperti predicted_label yang tersimpan di tabel products.
     * Dihapus oleh Concerns\InvalidatesPredictionCache begitu data Produk/Distribusi berubah.
     */
    public const ANALYSIS_CACHE_KEY = 'c45_analysis';

    /**
     * Tampilkan hasil klasifikasi produk (predicted_label) terakhir.
     */
    public function index()
    {
        $products = Product::orderByDesc('last_classified_at')
            ->orderBy('name')
            ->get();

        $averageStock = round($products->avg('stock') ?? 0);

        $analysis = Cache::get(self::ANALYSIS_CACHE_KEY, []);

        $tree = $analysis['tree'] ?? null;
        $accuracy = $analysis['accuracy'] ?? null;
        $totalTraining = $analysis['total'] ?? null;
        $p33 = $analysis['p33'] ?? null;
        $p66 = $analysis['p66'] ?? null;
        $averageQuantity = $analysis['average_quantity'] ?? null;
        $frequencyLevels = $analysis['frequency_levels'] ?? [];

        return view('predictions.index', compact('products', 'averageStock', 'tree', 'accuracy', 'totalTraining', 'p33', 'p66', 'averageQuantity', 'frequencyLevels'));
    }

    /**
     * Jalankan proses klasifikasi C4.5:
     * 1. Agregasi data distribusi per produk (total kuantitas, frekuensi).
     * 2. Diskritisasi frekuensi jadi Rendah/Sedang/Tinggi (tercile).
     * 3. Tentukan label training (Laris/Tidak Laris) dari total kuantitas vs rata-rata.
     * 4. Bangun pohon keputusan dan klasifikasikan tiap produk.
     * 5. Simpan predicted_label + last_classified_at ke tabel products.
     */
    public function process(): RedirectResponse
    {
        $products = Product::withCount('distributions')
            ->withSum('distributions', 'quantity')
            ->get();

        if ($products->isEmpty()) {
            return redirect()->route('predictions.index')->with('error', 'Belum ada data produk untuk dianalisis.');
        }

        if ($products->sum('distributions_count') === 0) {
            return redirect()->route('predictions.index')->with('error', 'Belum ada data distribusi untuk dianalisis.');
        }

        $frequencies = $products->pluck('distributions_count')->all();
        $p33 = $this->percentile($frequencies, 33);
        $p66 = $this->percentile($frequencies, 66);

        $averageQuantity = $products->avg(fn (Product $product) => (int) ($product->distributions_sum_quantity ?? 0));

        $dataset = $products->map(function (Product $product) use ($p33, $p66, $averageQuantity) {
            $totalQuantity = (int) ($product->distributions_sum_quantity ?? 0);

            return [
                'product_id' => $product->id,
                'category' => $product->category,
                'size' => $product->size,
                'frequency_level' => $this->frequencyLevel($product->distributions_count, $p33, $p66),
                'label' => $totalQuantity >= $averageQuantity ? 'Laris' : 'Tidak Laris',
            ];
        })->all();

        $classifier = new C45Classifier();
        $tree = $classifier->buildTree($dataset, ['category', 'size', 'frequency_level']);

        $correct = 0;
        foreach ($dataset as $row) {
            $predictedLabel = $classifier->classify($row);

            Product::whereKey($row['product_id'])->update([
                'predicted_label' => $predictedLabel,
                'last_classified_at' => now(),
            ]);

            if ($predictedLabel === $row['label']) {
                $correct++;
            }
        }

        $accuracy = round(($correct / count($dataset)) * 100, 1);

        Cache::forever(self::ANALYSIS_CACHE_KEY, [
            'tree' => $tree,
            'accuracy' => $accuracy,
            'total' => count($dataset),
            'p33' => $p33,
            'p66' => $p66,
            'average_quantity' => $averageQuantity,
            'frequency_levels' => collect($dataset)->pluck('frequency_level', 'product_id')->all(),
            'analyzed_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('predictions.index')->with(
            'success',
            "Analisis selesai. Akurasi model terhadap data training: {$accuracy}% ({$correct}/".count($dataset).' produk).'
        );
    }

    private function frequencyLevel(int $frequency, float $p33, float $p66): string
    {
        if ($frequency <= $p33) {
            return 'Rendah';
        }

        if ($frequency <= $p66) {
            return 'Sedang';
        }

        return 'Tinggi';
    }

    /**
     * Hitung nilai persentil ke-p dari sekumpulan angka (linear interpolation).
     *
     * @param  array<int, int>  $values
     */
    private function percentile(array $values, float $percentile): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        $fraction = $index - $lower;

        return $values[$lower] + $fraction * ($values[$upper] - $values[$lower]);
    }
}
