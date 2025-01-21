<?php

namespace Stillat\Dagger\Parser;

use Illuminate\Support\Str;
use PhpParser\Error;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\PrettyPrinter;
use PhpParser\PrettyPrinter\Standard;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Parser\DocumentParser;
use Stillat\Dagger\Compiler\ComponentState;
use Stillat\Dagger\Compiler\Concerns\CompilesBasicComponents;
use Stillat\Dagger\Compiler\Concerns\CompilesPhp;
use Stillat\Dagger\Facades\SourceMapper;
use Stillat\Dagger\Parser\Visitors\ComponentModelVisitor;
use Stillat\Dagger\Parser\Visitors\FullyQualifiedNamespaceVisitor;
use Stillat\Dagger\Parser\Visitors\StringifyMethodArgsVisitor;
use Stillat\Dagger\Support\Utils;

class ComponentParser
{
    use CompilesBasicComponents,
        CompilesPhp;

    protected array $componentNamespaces = [];

    protected PrettyPrinter $printer;

    protected int $originalLineCount = 0;

    protected bool $evaluate = true;

    public function __construct()
    {
        $this->printer = new Standard;
    }

    public function setEvaluateModel(bool $evalModel): static
    {
        $this->evaluate = $evalModel;

        return $this;
    }

    public function setComponentNamespaces(array $componentNamespaces): static
    {
        $this->componentNamespaces = $componentNamespaces;

        return $this;
    }

    public function parse(?ComponentNode $component, string $template, string $varSuffix, string $path = ''): ComponentState
    {
        $this->originalLineCount = mb_substr_count($template, "\n");

        $parser = PhpParser::makeParser();

        try {
            $ast = $parser->parse($template);
        } catch (Error $parseError) {
            throw SourceMapper::convertParserError($parseError, $template, $path);
        }

        $traverser = new NodeTraverser;
        $componentModelVisitor = new ComponentModelVisitor;
        $parentingVisitor = new ParentConnectingVisitor;
        $traverser->addVisitor($parentingVisitor);
        $traverser->traverse($ast);

        $traverser->removeVisitor($parentingVisitor);
        $traverser->addVisitor($componentModelVisitor);
        $traverser->traverse($ast);
        $componentChain = $componentModelVisitor->getComponentChain();

        $componentState = new ComponentState($component, $varSuffix);
        $componentState->setCompileVariableName('component');

        if ($componentChain === null) {
            return $this->fillComponentDetails($componentState, $template);
        }

        $componentModelAst = [];
        $newAst = [];
        $addedComponentChain = false;

        foreach ($ast as $node) {
            if ($node instanceof Nop) {
                continue;
            }

            if ($node instanceof Use_ || $node instanceof GroupUse) {
                $newAst[] = $node;
            }

            if ($addedComponentChain) {
                $newAst[] = $node;

                continue;
            }

            if ($node instanceof InlineHTML) {
                $newAst[] = $node;
            }

            if ($node instanceof Expression && $node->expr instanceof Assign) {
                if ($node !== $componentChain) {
                    $newAst[] = $node;
                }
            }

            if (! $node instanceof InlineHTML) {
                $componentModelAst[] = $node;
            }

            if ($node === $componentChain) {
                $addedComponentChain = true;
            }
        }

        $componentCall = $componentModelVisitor->getComponentCall();

        if ($componentCall instanceof FuncCall) {
            $componentCall->args[] = new Arg(new Variable('__componentTap'));
        }

        $variableName = 'component';

        if ($componentChain->expr instanceof Assign) {
            $variableName = $componentChain->expr->var->name;
        }

        $traverser->removeVisitor($componentModelVisitor);

        $traverser->addVisitor(new FullyQualifiedNamespaceVisitor);
        $methodStringifyVisitor = new StringifyMethodArgsVisitor(['props', 'aware', 'validateProps', 'mixin']);
        $traverser->addVisitor($methodStringifyVisitor);

        $traverser->traverse($componentModelAst);

        $model = $this->evaluateComponentModel($this->printer->prettyPrintFile($componentModelAst));
        $innerTemplate = $this->printer->prettyPrintFile($newAst);

        $componentState->shouldCache = $model->getShouldCache();
        $componentState->trimOutput = $model->getTrimOutput();
        $componentState->setCompileVariableName($variableName);

        $validationRules = $model->getPropValidationRules();

        if ($model->getProps() != '') {
            [$keys, $defaults, $propValidationRules] = $this->getValidationRulesFromProps(...ArrayValuesParser::parseArrayKeysAndValues($model->getProps()));

            if ($propValidationRules !== null) {
                $validationRules = $propValidationRules;
            }

            $componentState->mergeProps($keys)->setPropDefaults($defaults);
        }

        if ($model->getAwareVariables() != '') {
            [$keys, $defaults] = ArrayValuesParser::parseArrayKeysAndValues($model->getAwareVariables());
            $componentState->mergeAware($keys)->setAwareDefaults($defaults);
        }

        $componentState->setPropsFromValidationRules($model->getPropsFromValidation())
            ->setPropValidationRules($validationRules)
            ->setMixins($model->getMixins())
            ->setValidationMessages($model->getValidationMessages());

        return $this->fillComponentDetails($componentState, $innerTemplate);
    }

    protected function getValidationRulesFromProps(array $keys, array $defaults): array
    {
        $rules = null;
        $newKeys = [];
        $newDefaults = [];

        foreach ($keys as $key) {
            if (! Str::contains($key, '|')) {
                $newKeys[] = $key;

                continue;
            }

            if ($rules === null) {
                $rules = [];
            }

            $newKey = Str::before($key, '|');
            $rule = Str::after($key, '|');

            $newKeys[] = $newKey;
            $rules[$newKey] = "'{$rule}'";
        }

        foreach ($defaults as $key => $value) {
            if (! Str::contains($key, '|')) {
                $newDefaults[$key] = $value;

                continue;
            }

            $newKey = Str::before($key, '|');
            $newDefaults[$newKey] = $value;
        }

        return [$newKeys, $newDefaults, $rules];
    }

    protected function fillComponentDetails(ComponentState $component, string $innerTemplate): ComponentState
    {
        $parser = new DocumentParser;
        $parser
            ->withoutCoreDirectives()
            ->setDirectiveNames(['props', 'aware']);

        $nodes = $parser
            ->registerCustomComponentTags($this->componentNamespaces)
            ->parseTemplate($innerTemplate)
            ->toDocument()
            ->getRootNodes()
            ->all();

        if ($compiledProps = $this->findDirectiveArgs($nodes, 'props')) {
            [$keys, $defaults] = ArrayValuesParser::parseArrayKeysAndValues($compiledProps);
            $component->mergeProps($keys)->setPropDefaults($defaults);
        }

        if ($compiledAware = $this->findDirectiveArgs($nodes, 'aware')) {
            [$keys, $defaults] = ArrayValuesParser::parseArrayKeysAndValues($compiledAware);
            $component->mergeAware($keys)->setAwareDefaults($defaults);
        }

        [$namedTemplates, $innerTemplate] = $this->extractStencils($nodes);

        $component->lineOffset = $this->originalLineCount - mb_substr_count($innerTemplate, "\n");

        return $component
            ->setTemplate($innerTemplate)
            ->setNamedTemplates($namedTemplates);
    }

    protected function findDirectiveArgs(array $nodes, string $directiveName): string
    {
        foreach ($nodes as $node) {
            if (! $node instanceof DirectiveNode) {
                continue;
            }

            if ($node->content == $directiveName) {
                if ($node->arguments == null) {
                    return '';
                }

                return $node->arguments->innerContent;
            }
        }

        return '';
    }

    protected function extractStencils(array $nodes): array
    {
        $newContent = '';
        $namedTemplates = [];

        foreach ($nodes as $node) {
            if ($node instanceof DirectiveNode) {
                continue;
            }

            if (! $node instanceof ComponentNode) {
                $newContent .= $node->content;

                continue;
            }

            [$componentPrefix] = $this->getPrefixedComponentName($node);

            if ($componentPrefix != 'stencil') {
                $newContent .= $this->compileBasicComponent($node);

                continue;
            }

            $templateReplacement = '__stencil::'.Str::random(32);
            $newContent .= $templateReplacement;

            $namedTemplates[Utils::normalizeComponentName($node->tagName)] = [trim($node->innerDocumentContent), $templateReplacement];
        }

        return [$namedTemplates, $newContent];
    }

    protected function evaluateComponentModel(string $compiled): Component
    {
        $compiled = trim($compiled);

        if (str_starts_with($compiled, '<?php')) {
            $compiled = mb_substr($compiled, 5);
        }

        if ($this->evaluate) {
            $func = function ($__componentTap) use ($compiled) {
                eval($compiled);
            };

            $tap = new ComponentTap;
            $func($tap);

            return $tap->component;
        }

        return new Component(null);
    }
}
