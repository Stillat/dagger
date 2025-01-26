@php
    \Stillat\Dagger\component()
        ->props(['title'])
        ->compiler(
            allowCtr: false
        );
@endphp

{{ $title }}