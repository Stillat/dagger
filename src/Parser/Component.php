<?php

namespace Stillat\Dagger\Parser;

use Stillat\Dagger\AbstractComponent;
use Stillat\Dagger\ComponentOptions;
use Stillat\Dagger\Exceptions\InvalidArgumentException;
use Stillat\Dagger\Support\Asserts;

class Component extends AbstractComponent
{
    use Asserts;

    protected string $props = '';

    protected array $validatePropKeys = [];

    protected array $validatePropRules = [];

    protected string $validationMessages = '';

    protected string $awareVariables = '';

    protected bool $trimOutput = false;

    protected bool $cacheOutput = false;

    protected string $mixins = '';

    protected ?ComponentOptions $options;

    /**
     * @throws InvalidArgumentException
     */
    public function props(array|string $props): static
    {
        $this->assertIsString($props, 'Supplied "props" variables must be a string.');

        $this->props = $props;

        return $this;
    }

    public function getComponentOptions(): ComponentOptions
    {
        return $this->options ?? new ComponentOptions;
    }

    public function validateProps(array|string $props, array|string $messages = []): static
    {
        $this->assertIsString($props, 'Supplied "props" variables must be a string.');

        [$keys, $rules] = ArrayValuesParser::parseArrayKeysAndValues($props);

        if (is_array($messages)) {
            $messages = '[]';
        }

        $this->assertIsString($messages, 'Validation messages must be a string.');

        $this->validationMessages = $messages;
        $this->validatePropKeys = $keys;
        $this->validatePropRules = $rules;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function aware(array|string $aware): static
    {
        $this->assertIsString($aware, 'Supplied "aware" variables must be a string.');

        $this->awareVariables = $aware;

        return $this;
    }

    public function compiler(?bool $allowCtr = null): static
    {
        $this->options = new ComponentOptions;
        $this->options->allowCtr = $allowCtr ?? $this->options->allowCtr;

        return $this;
    }

    public function trimOutput(): static
    {
        $this->trimOutput = true;

        return $this;
    }

    public function getProps(): string
    {
        return $this->props;
    }

    public function getPropValidationRules(): array
    {
        return $this->validatePropRules;
    }

    public function getPropsFromValidation(): array
    {
        return $this->validatePropKeys;
    }

    public function getAwareVariables(): string
    {
        return $this->awareVariables;
    }

    public function getTrimOutput(): bool
    {
        return $this->trimOutput;
    }

    public function cache(): static
    {
        $this->cacheOutput = true;

        return $this;
    }

    public function getShouldCache(): bool
    {
        return $this->cacheOutput;
    }

    public function mixin(array|string $classes): static
    {
        $this->mixins = $classes;

        return $this;
    }

    public function getMixins(): string
    {
        return $this->mixins;
    }

    public function getValidationMessages(): string
    {
        return $this->validationMessages;
    }
}
