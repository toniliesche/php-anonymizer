<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\UnknownRuleSetException;

interface RuleSetProviderInterface
{
    /**
     * @throws UnknownRuleSetException
     */
    public function getRuleSet(string $name): RuleSet;

    public function registerRuleSet(string $name, RuleSet $ruleSet): void;
}
