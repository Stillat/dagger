@props(['title'])

Child One: {{ $title }}

<c-ctr.child_two :title="$title.' to child two'" />