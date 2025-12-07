<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\UnknownRuleSetException;

final class RuleSetProvider implements RuleSetProviderInterface
{
    /** @var RuleSet[] */
    private array $ruleSets = [];

    public function getRuleSet(string $name): RuleSet
    {
        return $this->ruleSets[$name] ?? throw new UnknownRuleSetException(sprintf('Rule set "%s" not found', $name));
    }

    public function registerRuleSet(string $name, RuleSet $ruleSet): void
    {
        $this->ruleSets[$name] = $ruleSet;
    }
}
