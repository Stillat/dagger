@php
    use function Stillat\Dagger\component;

    component()
        ->props(['title'])
        ->validateProps([
            'title' => 'required',
        ], [
            'required' => 'A custom message for :attribute.',
        ])
        ->trimOutput();
@endphp

{{ $title }}