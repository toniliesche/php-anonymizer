<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;

final readonly class ArrayRuleLoader implements RuleLoaderInterface
{
    /**
     * @param array<string,array<mixed>|string> $rules
     */
    public function __construct(private array $rules)
    {
    }

    public function loadRules(): Generator
    {
        foreach ($this->rules as $rule) {
            yield $rule;
        }
    }
}
