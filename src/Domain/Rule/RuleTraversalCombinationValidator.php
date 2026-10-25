<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;

/**
 * @internal
 */
final class RuleTraversalCombinationValidator
{
    public static function assert(bool $hasChildren, bool $hasEach, bool $hasStrategy, bool $hasEmbedded, bool $hasTransform): void
    {
        self::assertOnlyOneTraversal($hasChildren, $hasEach);
        self::assertLeafOptions($hasChildren || $hasEach, $hasStrategy, $hasEmbedded, $hasTransform);
        self::assertOnlyOneLeafOperation($hasStrategy, $hasEmbedded, $hasTransform);
    }

    private static function assertOnlyOneTraversal(bool $hasChildren, bool $hasEach): void
    {
        if ($hasChildren && $hasEach) {
            throw new InvalidProfileDefinition('a rule cannot contain both children and each');
        }
    }

    private static function assertLeafOptions(bool $hasTraversal, bool $hasStrategy, bool $hasEmbedded, bool $hasTransform): void
    {
        if ($hasTraversal && ($hasStrategy || $hasEmbedded || $hasTransform)) {
            throw new InvalidProfileDefinition('strategy, transform, and embedded require a leaf rule');
        }
    }

    private static function assertOnlyOneLeafOperation(bool $hasStrategy, bool $hasEmbedded, bool $hasTransform): void
    {
        if ($hasStrategy && $hasEmbedded) {
            throw new InvalidProfileDefinition('a rule cannot contain both strategy and embedded');
        }
        if (($hasStrategy && $hasTransform) || ($hasEmbedded && $hasTransform)) {
            throw new InvalidProfileDefinition('a rule cannot contain more than one of strategy, transform, or embedded');
        }
    }
}
