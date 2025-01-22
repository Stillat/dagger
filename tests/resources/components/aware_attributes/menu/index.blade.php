@props(['color'])

<div {{ $attributes->merge(['class' => "bg-{$color}-200"]) }}>
    {{ $slot }}
</div>