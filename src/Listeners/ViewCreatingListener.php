<?php

namespace Stillat\Dagger\Listeners;

use Stillat\Dagger\Runtime\ViewManifest;

class ViewCreatingListener
{
    protected ViewManifest $manifest;

    public function __construct(ViewManifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function create($view): void
    {
        $this->manifest->push($view);

        $this->checkForInvalidation($view);
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
