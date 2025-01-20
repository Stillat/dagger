<?php

namespace Stillat\Dagger\Support;

use Stillat\Dagger\Exceptions\InvalidArgumentException;

trait Asserts
{
    /**
     * @throws InvalidArgumentException
     */
    protected function assertIsString(mixed $value, string $message = ''): void
    {
        if (is_string($value)) {
            return;
        }

        throw new InvalidArgumentException($message);
    }
}
