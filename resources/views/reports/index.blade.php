<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-denim leading-tight">
            {{ __('Laporan Distribusi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            @if ($rangeError)
                <div role="alert" class="flex items-start gap-3 bg-surface border border-denim/10 border-l-4 border-l-brick rounded-xl shadow-sm px-4 py-3.5 print:hidden">
                    <svg class="w-5 h-5 text-brick shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-sans text-sm text-ink">{{ $rangeError }}</p>
                </div>
            @endif

            <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6 print:hidden">
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="from" value="Tanggal Awal" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 font-mono" :value="$from->toDateString()" />
                    </div>
                    <div>
                        <x-input-label for="to" value="Tanggal Akhir" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 font-mono" :value="$to->toDateString()" />
                    </div>
                    <x-primary-button type="submit">{{ __('Tampilkan') }}</x-primary-button>
                    <x-secondary-button type="button" onclick="window.print()">
                        {{ __('Cetak') }}
                    </x-secondary-button>
                </form>
            </div>

            <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-6">
                <div class="mb-4">
                    <h3 class="font-display font-semibold text-denim">Laporan Distribusi Toko Yery Collection</h3>
                    <p class="font-sans text-sm text-ink/60">
                        Periode: <span class="font-mono">{{ $from->translatedFormat('d M Y') }}</span> &mdash; <span class="font-mono">{{ $to->translatedFormat('d M Y') }}</span>
                    </p>
                </div>

                <div class="overflow-x-auto -mx-6 sm:mx-0 print:overflow-visible">
                    <table class="min-w-[640px] w-full text-sm">
                        <thead class="bg-denim text-white font-sans print:bg-transparent print:text-ink print:border-b-2 print:border-ink/30">
                            <tr class="text-left">
                                <th class="py-3 px-4 font-medium print:px-0">Tanggal</th>
                                <th class="py-3 px-4 font-medium print:px-2">Produk</th>
                                <th class="py-3 px-4 font-medium print:px-2">Kategori</th>
                                <th class="py-3 px-4 font-medium print:px-2">Ukuran</th>
                                <th class="py-3 px-4 font-medium text-right print:px-0">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono text-sm text-ink">
                            @forelse ($distributions as $distribution)
                                <tr class="odd:bg-cream even:bg-surface border-b border-denim/10 hover:bg-denim/5 print:hover:bg-transparent transition-colors print:bg-transparent print:border-ink/10">
                                    <td class="py-2.5 px-4 whitespace-nowrap print:px-0">{{ $distribution->distribution_date->translatedFormat('d M Y') }}</td>
                                    <td class="py-2.5 px-4 font-sans print:px-2">{{ $distribution->product->name }}</td>
                                    <td class="py-2.5 px-4 font-sans text-ink/60 print:px-2">{{ $distribution->product->category }}</td>
                                    <td class="py-2.5 px-4 font-sans text-ink/60 print:px-2">{{ $distribution->product->size }}</td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap print:px-0">{{ $distribution->quantity }} {{ $distribution->product->unit }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-ink/40 font-sans">
                                        {{ $rangeError ? 'Perbaiki rentang tanggal untuk menampilkan laporan.' : 'Tidak ada data distribusi pada periode ini.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($distributions->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-denim/20 font-sans font-semibold text-ink print:border-ink/30">
                                    <td colspan="4" class="py-3 px-4 text-right print:px-2">Total</td>
                                    <td class="py-3 px-4 text-right font-mono whitespace-nowrap print:px-0">{{ $distributions->sum('quantity') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
