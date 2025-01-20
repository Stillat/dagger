<?php

namespace Stillat\Dagger\Compiler;

class Extractions
{
    public array $stencils = [];

    /**
     * @var ExtractedSlot[]
     */
    public array $namedSlots = [];

    /**
     * @var ExtractedSlot[]
     */
    public array $forwardedSlots = [];

    public string $content = '';
}
