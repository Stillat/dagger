@props(['title'])

<style>
    @media screen {

    }
</style>

The Title: {{ $title }}
@{{ $title }}
@{!! $title !!}
@@if
@if ($title != '') Yes @else no @endif