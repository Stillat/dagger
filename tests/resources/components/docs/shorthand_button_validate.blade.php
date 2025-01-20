@php
    use function Stillat\Dagger\component;

    component()
        ->props(['title|required']);
@endphp

{{ $title }}