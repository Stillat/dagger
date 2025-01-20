<?php
use Some\Namespace\{
    function myFunction as aliasedFunction
};
?>

@props(['title'])

{{ aliasedFunction($title) }}