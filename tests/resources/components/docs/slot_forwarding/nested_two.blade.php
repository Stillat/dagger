<!-- /resources/dagger/views/panel.blade.php -->
<div {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

{{ $slot }}

<div {{ $slots->footer->attributes }}>
    {{ $slots->footer }}
</div>