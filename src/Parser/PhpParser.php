<?php

namespace Stillat\Dagger\Parser;

use PhpParser\Parser;
use PhpParser\ParserFactory;

class PhpParser
{
    public static function makeParser(): Parser
    {
        return (new ParserFactory)->createForHostVersion();
    }
}
