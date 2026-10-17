@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center whitespace-nowrap px-1 pt-1 border-b-2 border-thread font-sans text-sm font-medium leading-5 text-denim focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center whitespace-nowrap px-1 pt-1 border-b-2 border-transparent font-sans text-sm font-medium leading-5 text-ink/60 hover:text-denim hover:border-denim/30 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
