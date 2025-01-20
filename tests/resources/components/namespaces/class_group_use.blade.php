<?php
use Some\Namespace\{
    MyClass,
    AnotherClass
};
?>

@props(['title'])

{{ MyClass::someMethod($title) }}
{{ AnotherClass::anotherMethod($title) }}