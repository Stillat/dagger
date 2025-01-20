@php
    use function Stillat\Dagger\component;
    use Stillat\Dagger\Tests\StaticTestHelpers;

    component()->cache();
@endphp

<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
    Var1: {{ StaticTestHelpers::counter() }}
</div>

{{ $slot ?? 'No Default Slot' }}
Var2: {{ StaticTestHelpers::counter() }}
