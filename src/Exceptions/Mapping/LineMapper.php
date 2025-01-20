<?php

namespace Stillat\Dagger\Exceptions\Mapping;

use Stillat\Dagger\Facades\SourceMapper as SourceMapperApi;

class LineMapper
{
    protected int $currentLine = 1;

    protected bool $inPhpBlock = false;

    protected string $result = '';

    protected bool $inHeredoc = false;

    protected function appendText(string $text): void
    {
        foreach (explode("\n", $text) as $line) {
            $this->appendLine($line);
        }
    }

    protected function appendLine(string $line): void
    {
        $this->currentLine += 1;
        $this->result .= $line;
        $this->result .= "\n";
        $this->result .= $this->makeLineMarker($this->currentLine);
    }

    protected function makeLineMarker(int $line): string
    {
        // We need to keep the |--- ---| format for compatibility with Blade's BladeMapper.
        return '/**  |---LINE:'.$line.'---| */';
    }

    protected function addLineMarker(): void
    {
        $line = $this->makeLineMarker($this->currentLine);

        if ($this->result === $line) {
            return;
        }

        $this->result .= $line;
    }

    protected function appendLines(string $text): void
    {
        foreach (explode("\n", $text) as $line) {
            $this->currentLine += 1;
            $this->result .= "\n";

            if ($line === '') {
                continue;
            }

            $this->addLineMarker();
            $this->result .= $line;
        }
    }

    protected function reset(): void
    {
        $this->currentLine = 1;
        $this->result = $this->makeLineMarker(1);
        $this->inPhpBlock = $this->inHeredoc = false;
    }

    public function insertLineNumbers(string $template): string
    {
        $this->reset();

        $template = SourceMapperApi::addBladeLineNumbers($template);
        $tokens = token_get_all($template);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                [$tokenId, $text] = $token;

                switch ($tokenId) {
                    case T_OPEN_TAG:
                    case T_OPEN_TAG_WITH_ECHO:
                        $this->addLineMarker();
                        $this->result .= $text;
                        $this->inPhpBlock = true;

                        if (str_ends_with($text, "\n")) {
                            $this->currentLine += 1;
                            $this->addLineMarker();
                        }

                        break;
                    case T_CLOSE_TAG:
                        $this->result .= $text;
                        $this->inPhpBlock = false;
                        break;
                    case T_START_HEREDOC:
                        $this->inHeredoc = true;
                        $this->result .= rtrim($text);
                        break;
                    case T_END_HEREDOC:
                        $this->inHeredoc = false;
                        $this->result .= $text;
                        break;
                    default:
                        if (! $this->inPhpBlock) {
                            $this->result .= $text;
                            $this->currentLine += mb_substr_count($text, "\n");
                            break;
                        }

                        if ($this->inHeredoc) {
                            $this->appendLines($text);
                            break;
                        }

                        $segments = explode("\n", $text);
                        foreach ($segments as $iSeg => $seg) {
                            if ($iSeg > 0) {
                                $this->currentLine += 1;
                                // We’re hitting a newline boundary.
                                $this->result .= "\n";
                                $this->addLineMarker();
                            }

                            $this->result .= $seg;
                        }

                        break;
                }

                continue;
            }

            $this->result .= $token;

            if ($token === "\n") {
                if ($this->inPhpBlock) {
                    $this->currentLine += 1;
                    $this->addLineMarker();
                } else {
                    $this->currentLine += 1;
                }
            }
        }

        return trim($this->result);
    }
}
