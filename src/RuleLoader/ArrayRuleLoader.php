<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;

final readonly class ArrayRuleLoader implements RuleLoaderInterface
{
    /**
     * @param array<string,array<mixed>|string> $config
     */
    public function __construct(private array $config)
    {
    }

    public function loadRules(): Generator
    {
        foreach ($this->config['rules'] as $ruleName => $rule) {
            yield $ruleName => $rule;
        }
    }
}
