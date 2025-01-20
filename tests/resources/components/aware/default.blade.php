@php
use function Stillat\Dagger\component;

component()
    ->aware(['color' => 'gray'])
    ->trimOutput();
@endphp

The color: {{ $color }}