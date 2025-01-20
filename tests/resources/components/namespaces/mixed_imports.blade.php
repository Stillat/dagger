<?php
use Illuminate\Support\Str;
use Some\Namespace\{
    MyClass,
    AnotherClass as RenamedClass,
    function myFunction,
    const SOME_CONST
};
?>

@props(['title'])

{{ Str::upper($title) }}
{{ MyClass::someMethod($title) }}
{{ RenamedClass::anotherMethod($title) }}
{{ myFunction($title) }}
{{ SOME_CONST }}