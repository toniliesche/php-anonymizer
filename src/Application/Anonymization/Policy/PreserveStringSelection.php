<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

final readonly class PreserveStringSelection
{
    public function __construct(private ReplacementStrategyRegistry $strategies) {}

    /** @param list<string> $anonymizedPaths */
    public function apply(string &$value, RuleDefinition $rule, string $path, string $profile, array &$anonymizedPaths): void
    {
        $strategyId = $rule->strategy();
        if ($rule->scalarAction() instanceof ScalarAction) {
            $strategyId = $rule->scalarAction()->value === 'redact' ? 'redact' : null;
        }
        if ($strategyId === null) {
            if ($rule->hasChildren() || $rule->eachStrings() || $rule->eachRules() !== null) {
                throw UnsupportedValue::at($profile, $path, $value);
            }
            return;
        }
        $strategy = $this->strategies->get($strategyId);
        if (!$strategy instanceof ReplacementStrategyInterface) {
            throw new UnsupportedValue(sprintf('Replacement strategy "%s" is not available.', $strategyId));
        }
        $value = $strategy->replace($value);
        $anonymizedPaths[] = $path;
    }
}
