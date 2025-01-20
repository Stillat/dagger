@php
    use function Stillat\Dagger\component;

    component()->cache();
@endphp

<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

{{ $slot ?? 'No Default Slot' }}