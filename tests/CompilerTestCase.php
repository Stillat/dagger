<?php

namespace Stillat\Dagger\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Orchestra\Testbench\TestCase;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Exceptions\Mapping\LineMapper;
use Stillat\Dagger\Facades\Compiler;
use Stillat\Dagger\Parser\ComponentParser;
use Stillat\Dagger\ServiceProvider;

class CompilerTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $viewPaths = $app['config']['view']['paths'];

        $viewPaths[] = __DIR__.'/resources/views';

        $app['config']->set('view.paths', $viewPaths);
    }

    protected function setUp(): void
    {
        parent::setUp();

        StaticTestHelpers::resetCounter();

        Artisan::call('view:clear');
        Compiler::addComponentViewPath('c', __DIR__.'/resources/components');
    }

    protected function insertLineNumbers(string $template): string
    {
        $mapper = new LineMapper;

        return $mapper->insertLineNumbers($template);
    }

    protected function compile(string $template): string
    {
        return Blade::compileString($template);
    }

    protected function render(string $template, array $data = []): string
    {
        return Blade::render($template, $data);
    }

    protected function parseComponent(string $template): ComponentState
    {
        $parser = new ComponentParser;
        $parser->setComponentNamespaces(['c']);

        return $parser->parse(null, $template, 'random');
    }
}
