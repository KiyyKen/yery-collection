<div class="flex gap-6 border-b border-denim/10 font-sans text-sm">
    @foreach ([
        'questionnaire.index' => 'Isi Kuesioner',
        'questionnaire.results' => 'Hasil Kuesioner',
    ] as $routeName => $label)
        <a
            href="{{ route($routeName) }}"
            @class([
                '-mb-px pb-3 border-b-2 transition-colors focus:outline-none focus-visible:text-denim',
                'border-thread text-denim font-semibold' => request()->routeIs($routeName),
                'border-transparent text-ink/50 hover:text-denim' => ! request()->routeIs($routeName),
            ])
            @if (request()->routeIs($routeName)) aria-current="page" @endif
        >{{ $label }}</a>
    @endforeach
</div>
