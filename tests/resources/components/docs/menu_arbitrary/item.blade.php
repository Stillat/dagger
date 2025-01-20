<!-- /resources/views/components/menu/item.blade.php -->

<li {{ $attributes->merge(['class' => 'text-'.$component->parent()->color.'-800']) }}>
    {{ $slot }}
</li>