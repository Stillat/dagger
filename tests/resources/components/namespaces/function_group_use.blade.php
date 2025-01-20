<?php
use Some\Namespace\{
    function myFunction,
    function anotherFunction
};
?>

@props(['title'])

{{ myFunction($title) }}
{{ anotherFunction($title) }}