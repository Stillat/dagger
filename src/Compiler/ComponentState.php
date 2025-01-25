<?php

namespace Stillat\Dagger\Compiler;

use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\Dagger\Support\Utils;

class ComponentState
{
    /**
     * Indicates if the template author has supplied a custom ID for the component.
     *
     * <c-component_name #id="theComponentName" />
     */
    public bool $hasUserSuppliedId = false;

    protected string $template = '';

    protected string $variableName = 'component';

    public string $viewPath = '';

    public string $componentPath = '';

    public string $compilerId = '';

    public bool $shouldCache = false;

    private ?string $cachePrefix = null;

    public array $boundScopeVariables = [];

    public array $cleanupVars = [];

    public array $compiledComponentAttributes = [];

    public bool $trimOutput = false;

    protected array $dynamicVariables = [];

    protected array $forwardedValues = [];

    protected array $aware = [];

    protected array $props = [];

    protected array $namedTemplates = [];

    protected array $propValidationRules = [];

    protected array $defaultPropValues = [];

    protected array $defaultAwareValues = [];

    protected array $hoistedNamedSlotVariables = [];

    public array $cacheReplacements = [];

    public string $mixins = '';

    public string $validationMessages = '[]';

    public int $lineOffset = 0;

    public function __construct(
        public ?ComponentNode $node,
        public string $varSuffix,
    ) {
        $this->updateNodeDetails($this->node, $this->varSuffix);
    }

    /**
     * @internal
     */
    public function updateNodeDetails(?ComponentNode $node, string $varSuffix): static
    {
        $this->node = $node;
        $this->varSuffix = $varSuffix;

        $this->compilerId = '{'.$this->node?->id ?? $this->varSuffix.'}';

        return $this;
    }

    public function getCachePrefix(): string
    {
        if ($this->cachePrefix == null) {
            $this->cachePrefix = sha1($this->componentPath);
        }

        return $this->cachePrefix;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function addNamedSlotHoistedVar(string $name, string $value, ?string $customPath = null): void
    {
        // Ensure we have unique var names, in the event we have multiple "hoisted".
        if ($customPath === null) {
            $customPath = Utils::makeRandomString();
        }
        $name = $customPath.$name;

        $this->hoistedNamedSlotVariables[$name] = $value;
    }

    public function getHoistedSlotVariables(): array
    {
        return $this->hoistedNamedSlotVariables;
    }

    public function getVariableName(): string
    {
        return $this->variableName;
    }

    public function setCompileVariableName(string $variableName): static
    {
        $this->variableName = $variableName;

        return $this;
    }

    protected function makeDynamicVariable(string $varName): string
    {
        if (array_key_exists($varName, $this->dynamicVariables)) {
            return $this->dynamicVariables[$varName];
        }

        $replacement = Utils::makeRandomString();

        $this->dynamicVariables[$varName] = '__compilerVar'.$replacement.$this->varSuffix;

        return $this->dynamicVariables[$varName];
    }

    public function getTemporaryRenderVar(): string
    {
        return $this->makeDynamicVariable('tmpVars');
    }

    public function getOutputVar(): string
    {
        return $this->makeDynamicVariable('componentOutputResult');
    }

    public function componentDataVar(): string
    {
        return $this->makeDynamicVariable('componentValues');
    }

    public function addForwardedVariable(string $name, string $value): void
    {
        $this->forwardedValues[$name] = $value;
    }

    public function getForwardedVariables(): array
    {
        return $this->forwardedValues;
    }

    public function makeForwardedVariableName(?string $variableName = null): string
    {
        if (! $variableName) {
            $variableName = Utils::makeRandomString();
        }

        return '__forwardedValue::'.$variableName;
    }

    public function clearForwardedVariables(): void
    {
        $this->forwardedValues = [];
    }

    public function getDynamicVariable(string $varName): string
    {
        if (! array_key_exists($varName, $this->dynamicVariables)) {
            throw new InvalidArgumentException("Dynamic variable {$varName} has not been created.");
        }

        return $this->dynamicVariables[$varName];
    }

    public function hasCreatedDynamicVariable(string $varName): bool
    {
        return array_key_exists($varName, $this->dynamicVariables);
    }

    public function getVariableForwardingVariable(): string
    {
        $forwarding = $this->makeDynamicVariable('componentValueForwardingContainer');

        if (! in_array($forwarding, $this->boundScopeVariables)) {
            $this->boundScopeVariables[] = $forwarding;
        }

        return $forwarding;
    }

    public function getGlobalScopeVariableName(): string
    {
        return $this->makeDynamicVariable('componentGlobalScope');
    }

    public function requiresGlobalScope(): bool
    {
        return $this->hasCreatedDynamicVariable('componentGlobalScope');
    }

    public function hasForwardingContainer(): bool
    {
        return $this->hasCreatedDynamicVariable('componentValueForwardingContainer');
    }

    public function setNamedTemplates(array $namedTemplates): static
    {
        $this->namedTemplates = $namedTemplates;

        return $this;
    }

    public function getNamedTemplates(): array
    {
        return $this->namedTemplates;
    }

    public function trimOutput(): static
    {
        $this->trimOutput = true;

        return $this;
    }

    public function setAwareDefaults(array $defaults): static
    {
        $this->defaultAwareValues = $defaults;

        return $this;
    }

    public function getAwareDefaults(): array
    {
        return $this->defaultAwareValues;
    }

    public function getAwareVariables(): array
    {
        return $this->aware;
    }

    public function getAwareVariablesAndDefaults(): array
    {
        $variables = $this->defaultAwareValues;

        foreach ($this->aware as $var) {
            if (! array_key_exists($var, $variables)) {
                $variables[] = $var;
            }
        }

        return $variables;
    }

    public function setPropDefaults(array $defaults): static
    {
        $this->defaultPropValues = array_merge(
            $this->defaultPropValues,
            $defaults
        );

        return $this;
    }

    public function getPropDefaults(): array
    {
        return $this->defaultPropValues;
    }

    public function mergeAware(array $aware): static
    {
        $this->aware = array_merge($aware);

        return $this;
    }

    public function mergeProps(array $props): static
    {
        $this->props = array_merge($props);

        return $this;
    }

    public function setPropsFromValidationRules(array $propNames): static
    {
        $this->props = array_merge($propNames, $this->props);

        return $this;
    }

    public function setPropValidationRules(array $propValidationRules): static
    {
        $this->propValidationRules = $propValidationRules;

        return $this;
    }

    public function getPropValidationRules(): array
    {
        return $this->propValidationRules;
    }

    public function getPropNames(): array
    {
        return array_values(array_unique(ComponentAttributeBag::extractPropNames($this->props)));
    }

    public function getTrimOutput(): bool
    {
        return $this->trimOutput;
    }

    public function setMixins(string $classes): static
    {
        $this->mixins = $classes;

        return $this;
    }

    public function getMixins(): string
    {
        return $this->mixins;
    }

    public function setValidationMessages(string $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    public function getValidationMessages(): string
    {
        return $this->validationMessages;
    }

    public function getCleanupVariables(): array
    {
        return collect(array_merge(
            $this->dynamicVariables,
            $this->cleanupVars
        ))->map(function ($var) {
            if (str_starts_with($var, '$')) {
                return $var;
            }

            return '$'.$var;
        })->all();
    }
}
