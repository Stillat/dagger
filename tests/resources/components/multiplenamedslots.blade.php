<div id="header" {{ $slots->header->attributes }}>
    {{ $slots->header }}
</div>

<div {{ $attributes }}>
    {{ $slot }}
</div>

<div id="footer" {{ $slots->footer->attributes }}>
    {{ $slots->footer }}
</div>