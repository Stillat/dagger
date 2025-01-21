<?php

namespace Stillat\Dagger;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use Stillat\Dagger\Commands\InstallCommand;
use Stillat\Dagger\Compiler\BladeComponentStacksCompiler;
use Stillat\Dagger\Compiler\TemplateCompiler;
use Stillat\Dagger\Exceptions\Mapping\LineMapper;
use Stillat\Dagger\Facades\Compiler;
use Stillat\Dagger\Listeners\TerminatingListener;
use Stillat\Dagger\Listeners\ViewCreatingListener;
use Stillat\Dagger\Runtime\ComponentEnvironment;
use Stillat\Dagger\Runtime\ViewManifest;
use Stillat\Dagger\Support\Utils;

class ServiceProvider extends IlluminateServiceProvider
{
    public function register()
    {
        $this->app->singleton(ViewManifest::class, function ($app) {
            return new ViewManifest(
                Str::finish(Utils::normalizePath($app['config']['view.compiled']), '/')
            );
        });

        $this->app->singleton(ComponentEnvironment::class, function () {
            return new ComponentEnvironment;
        });

        $this->app->singleton(TemplateCompiler::class, function ($app) {
            $compiler = new TemplateCompiler(
                $app->make(ViewManifest::class),
                $app->make(Factory::class),
                $app->make(LineMapper::class),
                $app['config']['view.compiled']
            );

            return $compiler;
        });
    }

    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'dagger');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }

        $this->bootEvents();

        Compiler::registerComponentPath(
            'c',
            resource_path('dagger/views'),
            'components',
        );

        /** @var BladeComponentStacksCompiler $componentStackCompiler */
        $componentStackCompiler = app(BladeComponentStacksCompiler::class);

        Blade::directive('component', function ($expression) use ($componentStackCompiler) {
            return $componentStackCompiler->compileComponent($expression);
        });

        Blade::directive('endComponentClass', function ($expression) use ($componentStackCompiler) {
            return $componentStackCompiler->compileEndComponentClass($expression);
        });

        Blade::prepareStringsForCompilationUsing(fn ($template) => Compiler::compile($template));
    }

    protected function bootEvents()
    {
        Event::listen(Terminating::class, TerminatingListener::class);
        view()->creator('*', ViewCreatingListener::class);
    }
}
