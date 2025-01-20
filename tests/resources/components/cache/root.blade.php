@php
use function Stillat\Dagger\component;

component()->trimOutput();
@endphp

@for ($i = 0; $i < 3; $i++)
<c-cache.cached_forwarded_attributes #id="inner" />
@endfor

