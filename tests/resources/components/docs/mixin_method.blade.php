<!-- /resources/dagger/views/profile.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \Stillat\Dagger\Tests\Mixins\ProfileMixin::class,
    ])->props(['name']);
@endphp

<div>
    {{ $sayHello($name) }}
</div>
