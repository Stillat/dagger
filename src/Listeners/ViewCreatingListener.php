<?php

namespace Stillat\Dagger\Listeners;

use Illuminate\Support\Str;
use Stillat\Dagger\Facades\Compiler;
use Stillat\Dagger\Runtime\ViewManifest;

class ViewCreatingListener
{
    protected ViewManifest $manifest;

    protected static array $checkedViews = [];

    public function __construct(ViewManifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function create($view): void
    {
        $this->checkForBladeRenderCall($view);

        $this->manifest->push($view);

        $this->checkForInvalidation($view);
    }

    protected function checkForBladeRenderCall($view): void
    {
        $name = $view->getName();

        if (! Str::startsWith($name, '__components::')) {
            return;
        }

        if (isset(static::$checkedViews[$name])) {
            return;
        }

        static::$checkedViews[$name] = true;

        $path = $view->getPath();

        if (! file_exists($path)) {
            return;
        }

        file_put_contents(
            $path,
            Compiler::resolveBlocks(file_get_contents($path))
        );
    }

    public static function clearCheckedViews(): void
    {
        static::$checkedViews = [];
    }

    protected function checkForInvalidation($view): void
    {
        $name = $view->getName();
        $manifestPath = $this->manifest->getStoragePath($name);

        if (! file_exists($manifestPath)) {
            return;
        }

        $dependencies = json_decode(file_get_contents($manifestPath), true);
        $cachePath = $dependencies['path'];

        if (! file_exists($cachePath)) {
            return;
        }

        foreach ($dependencies['deps'] as $path => $lastModifiedTime) {
            if (! file_exists($path)) {
                @unlink($manifestPath);
                @unlink($cachePath);

                break;
            }

            if (filemtime($path) <= $lastModifiedTime) {
                continue;
            }

            @unlink($cachePath);
            @unlink($manifestPath);
        }
    }
}
