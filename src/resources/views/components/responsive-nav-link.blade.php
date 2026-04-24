@props(['active'])

@php
$classes = ($active ?? false)
            ? 'lc-app-nav-link-mobile is-active'
            : 'lc-app-nav-link-mobile';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
