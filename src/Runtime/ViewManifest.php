<?php

namespace Stillat\Dagger\Runtime;

use Illuminate\Contracts\View\View;

class ViewManifest
{
    protected bool $trace = true;

    protected array $views = [];

    protected array $tracked = [];

    protected string $cachePath = '';

    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    protected function getViewName(string $path): string
    {
        return (string) str(basename($path))
            ->substr(0, -5)
            ->substr(19);
    }

    public function appendTo(array $paths, array $manifest): void
    {
        foreach ($paths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $viewName = $this->getViewName($path);
            $this->tracked[$viewName] = json_decode(file_get_contents($path), true);
        }

        foreach ($manifest as $root => $details) {
            if (! array_key_exists($root, $this->tracked)) {
                $this->tracked[$root] = $details;

                continue;
            }

            $this->tracked[$root]['deps'] = array_merge(
                $this->tracked[$root]['deps'],
                $details['deps'],
            );
        }
    }

    public function getRootView(): ?View
    {
        if (empty($this->views)) {
            return null;
        }

        return $this->views[array_key_first($this->views)];
    }

    public function push($view): void
    {
        if (! $this->trace) {
            return;
        }

        $this->views[] = $view;
    }

    public function withoutTracing($callable): mixed
    {
        $this->trace = false;

        try {
            return $callable();
        } finally {
            $this->trace = true;
        }
    }

    public function getViews(): array
    {
        return collect($this->views)
            ->map(fn ($view) => $view->getName())
            ->all();
    }

    public function last(): ?View
    {
        if (count($this->views) === 0) {
            return null;
        }

        return $this->views[array_key_last($this->views)];
    }

    public function track(string $viewName, string $cachePath, string $path): void
    {
        if (! array_key_exists($viewName, $this->tracked)) {
            $this->tracked[$viewName] = [
                'path' => $cachePath,
                'deps' => [],
            ];
        }

        if (array_key_exists($path, $this->tracked[$viewName]['deps'])) {
            return;
        }

        $this->tracked[$viewName]['deps'][$path] = filemtime($path);
    }

    public function getRootStoragePaths(): array
    {
        return collect(array_keys($this->tracked))
            ->map(fn ($rootView) => $this->getStoragePath($rootView))
            ->all();
    }

    public function getStoragePath(string $rootView): string
    {
        return $this->cachePath."_compiler_manifest_{$rootView}.json";
    }

    public function getTracked(): array
    {
        return $this->tracked;
    }
}
