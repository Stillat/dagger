@aware(['color'])

<li {{ $attributes->merge(['class' => "text-{$color}-800"]) }}>{{ $slot }}</li>