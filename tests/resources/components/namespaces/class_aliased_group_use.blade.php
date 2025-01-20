<?php
use Some\Namespace\{
    MyClass as AliasedClass
};
?>

@props(['title'])

{{ AliasedClass::someMethod($title) }}