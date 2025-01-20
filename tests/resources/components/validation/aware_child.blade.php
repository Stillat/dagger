@php
    \Stillat\Dagger\component()
        ->props(['title'])->aware(['title'])
        ->validateProps([
            'title' => 'required',
        ])
        ->trimOutput();
@endphp

{{ $component->name }}: {{ $title }}
