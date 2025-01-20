<?php

namespace Stillat\Dagger\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dagger:install';

    protected $description = 'Scaffolds paths for Dagger component development.';

    public function handle()
    {
        $this->info('Scaffolding Dagger paths...');

        $path = resource_path('dagger/views');

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $this->info('Scaffolding Dagger paths... done!');
    }
}
