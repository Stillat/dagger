@php
\Stillat\Dagger\component()
    ->props(['text'])
    ->trimOutput();
@endphp

<button {{ $attributes }}>{{ $text }}</button>
