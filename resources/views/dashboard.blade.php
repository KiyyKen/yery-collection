<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-denim leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            {{-- Ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-denim/5 text-denim flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25-4.5L3.75 7.5m16.5 0l-8.25 4.5m8.25-4.5v9l-8.25 4.5M3.75 7.5l8.25 4.5m-8.25-4.5v9l8.25 4.5m0-9v9" />
                            </svg>
                        </div>
                        <p class="font-sans text-sm text-ink/60">Total Produk</p>
                    </div>
                    <p class="font-mono text-3xl font-semibold text-denim">{{ $totalProducts }}</p>
                </div>
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-thread/10 text-thread flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                        </div>
                        <p class="font-sans text-sm text-ink/60">Total Distribusi</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <p class="font-mono text-3xl font-semibold text-denim">{{ $totalDistributions }}</p>
                        @if (! is_null($distributionTrend))
                            <span class="inline-flex items-center gap-0.5 font-mono text-xs font-semibold {{ $distributionTrend >= 0 ? 'text-moss' : 'text-brick' }}">
                                {{ $distributionTrend >= 0 ? '▲' : '▼' }}{{ abs($distributionTrend) }}%
                            </span>
                        @endif
                    </div>
                    @if (! is_null($distributionTrend))
                        <p class="font-sans text-xs text-ink/40 mt-1">dibanding bulan lalu</p>
                    @endif
                </div>
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-moss/10 text-moss flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <p class="font-sans text-sm text-ink/60">Distribusi Hari Ini</p>
                    </div>
                    <p class="font-mono text-3xl font-semibold text-denim">{{ $todayDistributions }}</p>
                </div>
            </div>

            {{-- Grafik --}}
            <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                <h3 class="font-display font-semibold text-denim mb-8">Grafik Distribusi 6 Bulan Terakhir</h3>

                @php
                    $max = max($chartData->max('total'), 1);
                    // Kalau datanya <=6 bulan, kolom melebar mengisi penuh area card (flex-1), container
                    // full width tanpa scroll -> sama seperti tampilan awal.
                    // Begitu datanya >6 bulan, tiap kolom dikunci lebar = (100% lebar container - gap)/6,
                    // BUKAN angka px tetap. Karena basis-nya persentase dari lebar container (bukan
                    // dibagi rata ke seluruh kolom yang ada), 9 kolom x masing-masing "1/6 container"
                    // otomatis totalnya 150% lebar container -> overflow & scrollable di layar manapun,
                    // sementara container sendiri tetap full width mengikuti card (nggak ada max-w).
                    $isOverflowing = $chartData->count() > 6;
                @endphp

                <div
                    class="overflow-x-auto pb-1"
                    x-data
                    x-init="$el.scrollLeft = $el.scrollWidth"
                >
                    <div class="flex items-end gap-3 sm:gap-6 h-64">
                        @foreach ($chartData as $item)
                            <div @class([
                                'flex flex-col items-center justify-end h-full',
                                'flex-1 min-w-[4.5rem]' => ! $isOverflowing,
                                'shrink-0 w-[calc((100%-3.75rem)/6)] sm:w-[calc((100%-7.5rem)/6)]' => $isOverflowing,
                            ])>
                                <span class="font-mono text-xs text-ink/60 mb-1">{{ $item['total'] }}</span>
                                <div
                                    class="w-full max-w-12 bg-thread rounded-t transition-all"
                                    style="height: {{ $item['total'] > 0 ? max(($item['total'] / $max) * 100, 4) : 0 }}%"
                                ></div>
                                <span class="font-sans text-xs text-ink/50 mt-2 whitespace-nowrap">{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Analisis Terakhir --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <h3 class="font-display font-semibold text-denim mb-4">Analisis Terakhir</h3>

                    @if ($lastAnalysis)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="font-sans text-xs text-ink/50">Tanggal</p>
                                <p class="font-mono text-sm font-medium text-denim mt-1.5">
                                    {{ $lastAnalysis['date']->translatedFormat('d M Y') }}
                                </p>
                                <p class="font-mono text-xs text-ink/40">{{ $lastAnalysis['date']->translatedFormat('H:i') }}</p>
                            </div>
                            <div>
                                <p class="font-sans text-xs text-ink/50">Produk Dianalisis</p>
                                <p class="font-mono text-2xl font-medium text-denim mt-1">{{ $lastAnalysis['total'] }}</p>
                            </div>
                            <div>
                                <p class="font-sans text-xs text-ink/50">Akurasi Model</p>
                                <p class="font-mono text-2xl font-medium text-moss mt-1">{{ $lastAnalysis['accuracy'] }}%</p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <div class="mx-auto w-10 h-10 rounded-full bg-thread/10 flex items-center justify-center mb-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-thread"></span>
                            </div>
                            <p class="font-sans text-sm text-ink/60">Belum ada analisis yang dijalankan.</p>
                            <a href="{{ route('predictions.index') }}" class="inline-block mt-2 font-sans text-sm font-medium text-thread hover:text-thread-light hover:underline focus:outline-none focus-visible:underline transition-colors">
                                Jalankan Analisis &rarr;
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Produk Perlu Restock --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display font-semibold text-denim">Produk Perlu Restock</h3>
                        <a href="{{ route('predictions.index') }}" class="font-sans text-sm text-thread hover:text-thread-light hover:underline focus:outline-none focus-visible:underline transition-colors">Lihat semua</a>
                    </div>
                    <ul class="divide-y divide-denim/10">
                        @forelse ($restockRecommendations as $product)
                            <li class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-sans text-sm text-ink truncate">{{ $product->name }}</p>
                                    <p class="font-mono text-xs text-ink/40">Stok: {{ $product->stock }} {{ $product->unit }}</p>
                                </div>
                                <span class="relative inline-flex items-center gap-2 pl-3.5 pr-3 py-1.5 bg-thread/10 text-thread text-xs font-semibold rounded-r-md border border-thread/20 whitespace-nowrap shrink-0 before:content-[''] before:w-2 before:h-2 before:rounded-full before:bg-thread">
                                    Perlu Restock
                                </span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-ink/40 font-sans text-sm">
                                @if (! $lastAnalysis)
                                    Jalankan analisis dulu untuk melihat rekomendasi restock.
                                @else
                                    Semua produk "Laris" stoknya masih di atas rata-rata.
                                @endif
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Produk Terlaris --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <h3 class="font-display font-semibold text-denim mb-4">Produk Terlaris (Top 5)</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-ink/50 border-b border-denim/10">
                                <th class="py-2 pr-4 font-sans font-medium">Produk</th>
                                <th class="py-2 pr-4 font-sans font-medium">Kategori</th>
                                <th class="py-2 font-sans font-medium text-right">Total Terdistribusi</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono">
                            @forelse ($topProducts as $product)
                                <tr class="border-b last:border-0 border-denim/10 hover:bg-denim/5 transition-colors">
                                    <td class="py-2 pr-4 text-ink">{{ $product->name }}</td>
                                    <td class="py-2 pr-4 text-ink/60 font-sans">{{ $product->category }}</td>
                                    <td class="py-2 text-right text-ink">{{ $product->distributions_sum_quantity ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-ink/40 font-sans">Belum ada data distribusi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Hasil Prediksi Terbaru --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display font-semibold text-denim">Hasil Prediksi Terbaru</h3>
                        <a href="{{ route('predictions.index') }}" class="font-sans text-sm text-thread hover:text-thread-light hover:underline focus:outline-none focus-visible:underline transition-colors">Lihat semua</a>
                    </div>
                    <ul class="divide-y divide-denim/10">
                        @forelse ($latestPredictions as $product)
                            <li class="py-2 flex items-center justify-between gap-3">
                                <span class="font-sans text-ink truncate min-w-0">{{ $product->name }}</span>
                                @if ($product->predicted_label === 'Laris')
                                    <span class="relative inline-flex items-center gap-2 pl-3.5 pr-3 py-1.5 bg-moss/10 text-moss text-xs font-semibold rounded-r-md border border-moss/20 whitespace-nowrap shrink-0 before:content-[''] before:w-2 before:h-2 before:rounded-full before:bg-moss">
                                        {{ $product->predicted_label }}
                                    </span>
                                @else
                                    <span class="relative inline-flex items-center gap-2 pl-3.5 pr-3 py-1.5 bg-brick/10 text-brick text-xs font-semibold rounded-r-md border border-brick/20 whitespace-nowrap shrink-0 before:content-[''] before:w-2 before:h-2 before:rounded-full before:bg-brick">
                                        {{ $product->predicted_label }}
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="py-6 text-center text-ink/40 font-sans text-sm">
                                Belum ada hasil prediksi. Jalankan analisis di menu Prediksi.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
