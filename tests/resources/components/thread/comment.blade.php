@props(['comment'])

<li>
    <span>{{ $comment['message'] }}</span>

    @if (isset($comment['comments']) && count($comment['comments']) > 0)
        <c-thread :comments="$comment['comments']" />
    @endif
</li>