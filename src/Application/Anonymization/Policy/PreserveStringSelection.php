<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ContextualReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ReplacementContext;
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

final readonly class PreserveStringSelection
{
    public function __construct(private ReplacementStrategyRegistry $strategies)
    {
    }

    /**
     * @param list<string> $anonymizedPaths
     */
    public function apply(string &$value, RuleDefinition $rule, string $path, string $profile, array &$anonymizedPaths, ?AnonymizationContext $context = null): void
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
        $value = $strategy instanceof ContextualReplacementStrategyInterface
            ? $this->contextualReplace($strategy, $value, $rule, $path, $profile, $context)
            : $strategy->replace($value);
        $anonymizedPaths[] = $path;
    }

    private function contextualReplace(ContextualReplacementStrategyInterface $strategy, string $value, RuleDefinition $rule, string $path, string $profile, ?AnonymizationContext $context): string
    {
        if (!$context instanceof AnonymizationContext || $rule->keyId() === null) {
            throw ContextRequired::forStrategy($profile, $path, $strategy->id());
        }

        return $strategy->replaceWithContext($value, new ReplacementContext($profile, JsonPointer::fromString($path), $rule->keyId(), $context));
    }
}
