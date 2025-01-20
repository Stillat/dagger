<?php

namespace Stillat\Dagger\Translation;

class Messages
{
    public static function getValidationMessages(array $messages = []): array
    {
        return array_merge(
            trans('validation'),
            trans('dagger::validation'),
            $messages
        );
    }
}
