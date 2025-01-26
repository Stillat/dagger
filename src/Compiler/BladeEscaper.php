<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use ReflectionClass;
use ReflectionMethod;

class BladeEscaper
{
    protected BladeCompiler $compiler;

    protected bool $hasResolvedCoreDirectives = false;

    protected bool $hasResolvedCustomDirectives = false;

    protected bool $hasResolvedTags = false;

    protected array $directives = [];

    protected array $rawTags = [];

    protected array $contentTags = [];

    protected array $escapedTags = [];

    protected array $ignoreCoreCompileMethods = [
        '',
        'string',
        'componenttags',
        'extensions',
        'statements',
        'statement',
        'comments',
    ];

    public function __construct(BladeCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    protected function resolveTags(): void
    {
        if ($this->hasResolvedTags) {
            return;
        }

        $this->rawTags = (fn () => $this->rawTags)->call($this->compiler);
        $this->contentTags = (fn () => $this->contentTags)->call($this->compiler);
        $this->escapedTags = (fn () => $this->escapedTags)->call($this->compiler);

        $this->hasResolvedTags = true;
    }

    protected function resolveCoreDirectives(): void
    {
        if ($this->hasResolvedCoreDirectives) {
            return;
        }

        $methods = (new ReflectionClass($this->compiler))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if (! Str::startsWith($method->name, 'compile')) {
                continue;
            }

            $directiveName = (string) str($method->name)
                ->after('compile')
                ->lower();

            if (in_array($directiveName, $this->ignoreCoreCompileMethods)) {
                continue;
            }

            $this->directives[] = $directiveName;
        }

        $this->hasResolvedCoreDirectives = true;
    }

    protected function resolveCustomDirectives(): void
    {
        if ($this->hasResolvedCustomDirectives) {
            return;
        }

        foreach (array_keys($this->compiler->getCustomDirectives()) as $directiveName) {
            $this->directives[] = mb_strtolower($directiveName);
        }

        $this->hasResolvedCustomDirectives = true;
    }

    protected function resolveDirectives(): void
    {
        $this->resolveCoreDirectives();
        $this->resolveCustomDirectives();
    }

    protected function escapeStatements(string $value): string
    {
        $pattern = '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x';

        $callback = function ($matches) {
            $candidate = mb_strtolower($matches[1]);

            if (! in_array($candidate, $this->directives)) {
                return $matches[0];
            }

            return '@'.$matches[0];
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function escapeRawEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        $callback = function ($matches) {
            return '@'.$matches[0];
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function escapeEscapedEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = function ($matches) {
            return $matches[0];
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function escapeRegularEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        $callback = function ($matches) {
            return '@'.$matches[0];
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function escapeEchos(string $value): string
    {
        $value = $this->escapeRawEchos($value);
        $value = $this->escapeEscapedEchos($value);

        return $this->escapeRegularEchos($value);
    }

    protected function escapeToken($token): string
    {
        [$id, $content] = $token;

        if ($id != T_INLINE_HTML) {
            return $content;
        }

        $content = $this->escapeStatements($content);

        return $this->escapeEchos($content);
    }

    public function escape(string $value): string
    {
        $this->resolveDirectives();
        $this->resolveTags();

        $result = '';

        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->escapeToken($token) : $token;
        }

        return $result;
    }
}
