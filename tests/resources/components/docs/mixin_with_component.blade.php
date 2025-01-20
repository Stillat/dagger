<!-- /resources/dagger/views/component.blade.php -->
@php
    \Stillat\Dagger\component()->mixin([
        \Stillat\Dagger\Tests\Mixins\ComponentMixin::class,
    ]);
@endphp

<div>
    {{ $name_upper }}
</div>
