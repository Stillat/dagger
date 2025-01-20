<?php
use Illuminate\Support\Str as SomethingElse;
?>

@props(['title'])

{{ SomethingElse::upper($title) }}