<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Tampilkan laporan distribusi pada rentang tanggal tertentu.
     *
     * Laporan dicetak lewat fitur print bawaan browser (tanpa package PDF tambahan).
     */
    public function index(Request $request)
    {
        $from = $this->queryDate($request, 'from') ?? now()->startOfMonth();
        $to = $this->queryDate($request, 'to') ?? now();

        // Rentang terbalik tidak di-query; halaman menampilkan pesan dan tabel kosong.
        if ($from->copy()->startOfDay()->gt($to->copy()->startOfDay())) {
            $distributions = new Collection();
            $rangeError = 'Tanggal awal tidak boleh melebihi tanggal akhir.';

            return view('reports.index', compact('distributions', 'from', 'to', 'rangeError'));
        }

        $distributions = Distribution::with('product')
            ->whereBetween('distribution_date', [$from, $to])
            ->orderBy('distribution_date')
            ->get();

        $rangeError = null;

        return view('reports.index', compact('distributions', 'from', 'to', 'rangeError'));
    }

    /**
     * Ambil parameter tanggal dari query string. Nilai yang bukan tanggal valid
     * (mis. diketik manual di URL) dianggap kosong, supaya laporan kembali ke
     * rentang default alih-alih error.
     */
    private function queryDate(Request $request, string $key): ?Carbon
    {
        try {
            return $request->date($key);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
