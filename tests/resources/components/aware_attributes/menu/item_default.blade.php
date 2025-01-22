@aware(['color' => 'just a default'])

<li {{ $attributes->merge(['class' => "text-{$color}-800"]) }}>{{ $slot }}</li>