@php
    \Stillat\Dagger\component()
        ->props(['title'])
        ->compiler(
            allowOptimizations: false
        );
@endphp

{{ $title }}