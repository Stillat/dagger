@php
use function Stillat\Dagger\component;

component()
    ->props(['title'])
    ->validateProps([
        'title' => 'required',
    ])
    ->trimOutput();
@endphp

{{ $title }}