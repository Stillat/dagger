<?php

namespace Stillat\Dagger\Listeners;

use Stillat\Dagger\Facades\Compiler;
use Stillat\Dagger\Runtime\ViewManifest;

class TerminatingListener
{
    protected ViewManifest $manifest;

    public function __construct(ViewManifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function handle()
    {
        Compiler::cleanup();

        foreach ($this->manifest->getTracked() as $rootView => $tracked) {
            $path = $this->manifest->getStoragePath($rootView);

            if (str_contains($path, '::')) {
                continue;
            }

            file_put_contents(
                $path,
                json_encode($tracked)
            );
        }
    }
}
