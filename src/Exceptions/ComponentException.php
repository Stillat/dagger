<?php

namespace Stillat\Dagger\Exceptions;

use Illuminate\View\ViewException;

class ComponentException extends ViewException
{
    public ?int $originalLineNumber = null;
}
