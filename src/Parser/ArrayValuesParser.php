<?php

namespace Stillat\Dagger\Parser;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\PrettyPrinter\Standard;

class ArrayValuesParser
{
    protected static function isValidAssignmentForKeys(Expression $node): bool
    {
        if (! $node->expr instanceof Assign) {
            return false;
        }

        return $node->expr->var instanceof Variable && $node->expr->var->name === 'properties';
    }

    protected static function parsePhpArray(string $values): array
    {
        $values = '<?php $properties = '.$values.';';
        $parser = PhpParser::makeParser();

        return $parser->parse($values);
    }

    protected static function isValidExpression($stmt): bool
    {
        if (! $stmt instanceof Expression) {
            return false;
        }

        if (! self::isValidAssignmentForKeys($stmt)) {
            return false;
        }

        return true;
    }

    public static function parseArrayKeysAndValues(string $values): array
    {
        $printer = new Standard;

        $keys = [];
        $defaults = [];

        foreach (self::parsePhpArray($values) as $stmt) {
            if (! self::isValidExpression($stmt)) {
                continue;
            }

            $expr = $stmt->expr;

            if (! $expr->expr instanceof Array_) {
                continue;
            }

            /** @var ArrayItem $item */
            foreach ($expr->expr->items as $item) {
                if ($item->key !== null) {
                    $key = $item->key->value;

                    $keys[] = $key;
                    $defaults[$key] = $printer->prettyPrint([$item->value]);
                } else {
                    if ($item->value instanceof String_) {
                        $keys[] = $item->value->value;
                    }
                }
            }

            break;
        }

        return [$keys, $defaults];
    }
}
