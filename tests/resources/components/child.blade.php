@php
use function Stillat\Dagger\{component, current, _parent};

$component = component()->props(['title']);
@endphp

Using class properties:
{{ $component?->parent->title }} from {{ $component?->parent->name }}
{{ $component->data->title }} from {{ $component->name }}

Using helpers:
{{ _parent()->title }} from {{ _parent()->name }}
{{ current()->title }} from {{ current()->name }}