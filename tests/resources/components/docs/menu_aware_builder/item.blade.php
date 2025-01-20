<!-- /resources/views/components/menu/item.blade.php -->
@php
    \Stillat\Dagger\component()->aware(['color' => 'gray']);
@endphp

<li {{ $attributes->merge(['class' => 'text-'.$color.'-800']) }}>
    {{ $slot }}
</li>