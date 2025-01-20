<?php

namespace Stillat\Dagger\Listeners;

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
        foreach ($this->manifest->getTracked() as $rootView => $tracked) {
            file_put_contents(
                $this->manifest->getStoragePath($rootView),
                json_encode($tracked)
            );
        }
    }
}
