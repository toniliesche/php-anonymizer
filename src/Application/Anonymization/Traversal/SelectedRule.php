<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal selected rule and its precompiled nested list
 */
final readonly class SelectedRule
{
    public function __construct(
        public RuleDefinition $rule,
        public ?CompiledRuleList $nested,
    ) {
    }
}
