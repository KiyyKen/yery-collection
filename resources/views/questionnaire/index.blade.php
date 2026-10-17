<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-denim leading-tight">
            {{ __('Kuesioner Kepuasan Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            @include('questionnaire._tabs')

            @if ($questions->isEmpty())
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-10 text-center font-sans">
                    <p class="text-ink/70">Belum ada pertanyaan kuesioner.</p>
                    <p class="text-ink/40 text-xs mt-1">Jalankan <span class="font-mono">php artisan db:seed --class=QuestionnaireQuestionSeeder</span> untuk mengisi pertanyaan.</p>
                </div>
            @elseif ($response)
                {{-- Sudah mengisi: tampilkan jawaban read-only --}}
                <div class="bg-moss/10 border border-moss/20 rounded-xl px-5 py-4 font-sans text-sm text-moss">
                    Anda sudah mengisi kuesioner pada
                    <span class="font-mono font-medium">{{ $response->submitted_at->translatedFormat('d M Y, H:i') }}</span>.
                    Jawaban yang sudah dikirim tidak dapat diubah.
                </div>

                <ol class="space-y-3">
                    @foreach ($questions as $question)
                        @php $score = $answers[$question->id] ?? null; @endphp
                        <li class="bg-surface border border-denim/10 rounded-xl shadow-sm p-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                            <div class="flex-1 min-w-0">
                                <p class="font-sans text-xs text-ink/50">
                                    <span class="font-mono">{{ $question->order }}.</span> {{ $question->aspect }}
                                </p>
                                <p class="font-sans text-ink mt-1">{{ $question->statement }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center gap-2 px-3 py-1.5 bg-denim/5 border border-denim/15 rounded-md font-sans text-sm text-denim">
                                <span class="font-mono font-semibold">{{ $score ?? '-' }}</span>
                                <span>{{ $score ? $scale[$score] : 'Tidak dijawab' }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @else
                {{-- Belum mengisi: form kuesioner --}}
                <div class="bg-surface border border-denim/10 rounded-xl shadow-sm p-5 font-sans text-sm text-ink/70">
                    Berikan penilaian Anda terhadap setiap pernyataan berikut. Semua pernyataan wajib dijawab,
                    dan jawaban hanya dapat dikirim satu kali.
                    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-ink/50">
                        @foreach ($scale as $value => $label)
                            <li><span class="font-mono font-semibold text-ink/70">{{ $value }}</span> = {{ $label }}</li>
                        @endforeach
                    </ul>
                </div>

                @error('answers')
                    <p class="font-sans text-sm text-brick">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('questionnaire.store') }}" class="space-y-3"
                    onsubmit="return confirm('Kirim jawaban kuesioner? Jawaban tidak dapat diubah setelah dikirim.');">
                    @csrf

                    @foreach ($questions as $question)
                        @php $fieldError = $errors->first('answers.'.$question->id); @endphp
                        <fieldset @class([
                            'bg-surface border rounded-xl shadow-sm p-5',
                            'border-brick/40' => $fieldError,
                            'border-denim/10' => ! $fieldError,
                        ])>
                            <legend class="sr-only">Pernyataan {{ $question->order }}</legend>
                            <p class="font-sans text-xs text-ink/50">
                                <span class="font-mono">{{ $question->order }}.</span> {{ $question->aspect }}
                            </p>
                            <p class="font-sans text-ink mt-1">{{ $question->statement }}</p>

                            <div class="mt-4 grid grid-cols-5 gap-2">
                                @foreach ($scale as $value => $label)
                                    <label class="relative cursor-pointer">
                                        <input
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="{{ $value }}"
                                            class="peer sr-only"
                                            @checked((string) old('answers.'.$question->id) === (string) $value)
                                            required
                                        >
                                        <span class="flex h-full flex-col items-center gap-1 rounded-md border border-denim/15 px-1 py-2.5 text-center transition-colors hover:border-denim/40 peer-checked:border-thread peer-checked:bg-thread/10 peer-focus-visible:ring-2 peer-focus-visible:ring-thread">
                                            <span class="font-mono text-base font-semibold text-denim">{{ $value }}</span>
                                            <span class="font-sans text-[11px] leading-tight text-ink/60">{{ $label }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @if ($fieldError)
                                <p class="mt-2 font-sans text-sm text-brick">{{ $fieldError }}</p>
                            @endif
                        </fieldset>
                    @endforeach

                    <div class="flex justify-end pt-2">
                        <x-primary-button>{{ __('Kirim Jawaban') }}</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
