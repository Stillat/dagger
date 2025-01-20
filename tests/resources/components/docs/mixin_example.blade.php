<!-- /resources/dagger/views/mixin.blade.php -->
@php
\Stillat\Dagger\component()->mixin([
    \Stillat\Dagger\Tests\Mixins\ThemeData::class,
])->props(['background']);
@endphp

<div {{ $attributes->merge(['class' => $background]) }}>
    ...
</div>
