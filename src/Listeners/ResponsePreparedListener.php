<?php

namespace Stillat\Dagger\Listeners;

use Stillat\Dagger\Facades\Compiler;
use Stillat\Dagger\Runtime\ViewManifest;

class ResponsePreparedListener
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
            file_put_contents(
                $this->manifest->getStoragePath($rootView),
                json_encode($tracked)
            );
        }
    }
}
