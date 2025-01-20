<!-- /resources/dagger/views/list/index.blade.php -->
@props(['items'])

<ul>
    @foreach ($items as $item)
        <c-stencil:list_item>
            <li>{{ $item }}</li>
        </c-stencil:list_item>
    @endforeach
</ul>