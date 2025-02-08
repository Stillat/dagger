<?php
\Stillat\Dagger\component()->props(['title'])->trimOutput();

function toUpper($value) {
  return mb_strtoupper($value);
}
?>

{{ toUpper($title) }}
