@props(['active'])

@php
$classes = ($active ?? false)
            ? 'lc-app-nav-link is-active'
            : 'lc-app-nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
