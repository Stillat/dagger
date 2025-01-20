<?php

namespace Stillat\Dagger\Runtime;

use Illuminate\Contracts\Support\Htmlable;

class EmptyHtmlable implements Htmlable
{
    protected static ?EmptyHtmlable $instance = null;

    public static function instance(): EmptyHtmlable
    {
        if (! self::$instance) {
            self::$instance = new EmptyHtmlable;
        }

        return self::$instance;
    }

    public function toHtml()
    {
        return '';
    }
}
