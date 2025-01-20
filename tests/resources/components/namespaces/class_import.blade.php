<?php
    use Illuminate\Support\Str;
?>

@props(['title'])

{{ Str::upper($title) }}