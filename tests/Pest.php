<?php

uses(\Stillat\Dagger\Tests\CompilerTestCase::class)
    ->in(
        'AttributeCache',
        'Cache',
        'Compiler',
        'LineNumbers',
        'Parsers',
        'Runtime',
    );
