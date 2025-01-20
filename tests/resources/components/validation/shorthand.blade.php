@php
    use function Stillat\Dagger\component;

    component()
        ->props([
            'title|required',
            'size|numeric|min:1|max:4' => 20
        ])
        ->trimOutput();
@endphp

{{ $title }} - {{ $size }}