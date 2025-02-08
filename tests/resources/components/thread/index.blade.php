@props(['comments'])

<ul>
    @foreach ($comments as $comment)
        <c-thread.comment :$comment />
    @endforeach
</ul>