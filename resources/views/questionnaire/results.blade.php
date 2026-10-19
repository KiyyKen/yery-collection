@php
    $formatIndex = fn (?float $value) => $value === null ? '—' : number_format($value, 2, ',', '.').'%';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-denim leading-tight">
            {{ __('Kuesioner Kepuasan Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            @include('questionnaire._tabs')

            @if ($summary['respondent_count'] === 0)
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-12 text-center font-sans">
                    <p class="text-ink/70">Belum ada pengguna yang mengisi kuesioner.</p>
                    <p class="text-ink/40 text-xs mt-1">Hasil akan tampil setelah minimal satu pengguna mengirim jawaban di tab "Isi Kuesioner".</p>
                </div>
            @else
                {{-- Ringkasan --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-5">
                        <p class="font-sans text-sm text-ink/60">Jumlah Responden</p>
                        <p class="font-mono text-3xl font-medium text-denim mt-1">{{ $summary['respondent_count'] }}</p>
                    </div>
                    <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-5">
                        <p class="font-sans text-sm text-ink/60">Indeks Keseluruhan</p>
                        <p class="font-mono text-3xl font-medium text-denim mt-1">{{ $formatIndex($summary['overall']['index']) }}</p>
                    </div>
                    <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-5">
                        <p class="font-sans text-sm text-ink/60">Interpretasi Keseluruhan</p>
                        <p class="font-display text-2xl font-semibold text-thread mt-2">{{ $summary['overall']['interpretation'] }}</p>
                    </div>
                </div>

                {{-- Rekap per pernyataan --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 pt-5 pb-4">
                        <h3 class="font-display font-semibold text-denim">Rekap per Pernyataan</h3>
                        <p class="font-sans text-xs text-ink/50 mt-1">
                            Indeks = total skor / (5 &times; {{ $summary['respondent_count'] }} responden) &times; 100%.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[960px] w-full text-sm">
                            <thead class="bg-denim text-white font-sans">
                                <tr>
                                    <th class="py-3.5 px-4 font-medium text-left">No</th>
                                    <th class="py-3.5 px-4 font-medium text-left">Pernyataan</th>
                                    @foreach ($scale as $value => $label)
                                        <th class="py-3.5 px-3 font-medium text-center" title="{{ $label }}">{{ $value }}</th>
                                    @endforeach
                                    <th class="py-3.5 px-4 font-medium text-right">Total Skor</th>
                                    <th class="py-3.5 px-4 font-medium text-right">Indeks</th>
                                    <th class="py-3.5 px-4 font-medium text-left">Interpretasi</th>
                                </tr>
                            </thead>
                            <tbody class="font-sans text-ink">
                                @foreach ($summary['questions'] as $row)
                                    <tr class="odd:bg-cream even:bg-surface border-b border-denim/10">
                                        <td class="py-3.5 px-4 font-mono align-top">{{ $row['question']->order }}</td>
                                        <td class="py-3.5 px-4 align-top">
                                            <span class="block text-xs text-ink/50">{{ $row['question']->aspect }}</span>
                                            {{ $row['question']->statement }}
                                        </td>
                                        @foreach ($row['distribution'] as $count)
                                            <td class="py-3.5 px-3 font-mono text-center align-top {{ $count === 0 ? 'text-ink/30' : '' }}">{{ $count }}</td>
                                        @endforeach
                                        <td class="py-3.5 px-4 font-mono text-right align-top">{{ $row['total'] }}</td>
                                        <td class="py-3.5 px-4 font-mono text-right align-top whitespace-nowrap">{{ $formatIndex($row['index']) }}</td>
                                        <td class="py-3.5 px-4 align-top whitespace-nowrap">{{ $row['interpretation'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="font-sans font-semibold text-denim bg-denim/5">
                                <tr>
                                    <td colspan="{{ 2 + count($scale) }}" class="py-3.5 px-4 text-right">Keseluruhan</td>
                                    <td class="py-3.5 px-4 font-mono text-right">{{ $summary['overall']['total'] }}</td>
                                    <td class="py-3.5 px-4 font-mono text-right whitespace-nowrap">{{ $formatIndex($summary['overall']['index']) }}</td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">{{ $summary['overall']['interpretation'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-denim/10 font-sans text-xs text-ink/50 space-y-1">
                        <p>
                            Skala:
                            @foreach ($scale as $value => $label)
                                <span class="font-mono">{{ $value }}</span> = {{ $label }}@if (! $loop->last), @endif
                            @endforeach
                        </p>
                        <p>
                            Interpretasi indeks:
                            @php $bounds = array_keys($interpretations); @endphp
                            @foreach ($interpretations as $lower => $label)
                                <span class="font-mono">{{ $lower }} ≤ x {{ $loop->last ? '≤ 100' : '< '.$bounds[$loop->index + 1] }}</span> {{ $label }}@if (! $loop->last), @endif
                            @endforeach
                        </p>
                    </div>
                </div>

                {{-- Daftar responden --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm overflow-hidden">
                    <h3 class="px-5 pt-5 pb-4 font-display font-semibold text-denim">Daftar Responden</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-[480px] w-full text-sm">
                            <thead class="bg-denim text-white font-sans">
                                <tr class="text-left">
                                    <th class="py-3.5 px-5 font-medium">No</th>
                                    <th class="py-3.5 px-5 font-medium">Nama</th>
                                    <th class="py-3.5 px-5 font-medium">Tanggal Mengisi</th>
                                </tr>
                            </thead>
                            <tbody class="font-sans text-ink">
                                @foreach ($summary['respondents'] as $respondent)
                                    <tr class="odd:bg-cream even:bg-surface border-b border-denim/10">
                                        <td class="py-3.5 px-5 font-mono">{{ $loop->iteration }}</td>
                                        <td class="py-3.5 px-5">{{ $respondent->user->name }}</td>
                                        <td class="py-3.5 px-5 font-mono whitespace-nowrap">{{ $respondent->submitted_at->translatedFormat('d M Y, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
