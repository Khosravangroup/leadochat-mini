@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-sm text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
