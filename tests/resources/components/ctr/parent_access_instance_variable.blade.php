@props(['title'])

Title: {{ $title }}
Parent: {{ $component->parent?->name ?? '' }}