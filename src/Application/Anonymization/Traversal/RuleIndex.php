<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal indexed rules for one traversal node
 *
 * @codeCoverageIgnore Internal traversal value.
 */
final readonly class RuleIndex
{
    /**
     * @param list<RuleDefinition> $rules
     * @param array<mixed, mixed> $snapshot
     */
    public function __construct(private array $rules, private array $snapshot, private bool $normalizeKeys, private bool $match = true)
    {
    }

    public function resolve(string $field): ?RuleDefinition
    {
        foreach ($this->rules as $rule) {
            if ($rule->field() === $field) {
                return $rule;
            }
        }
        if (!$this->normalizeKeys) {
            return null;
        }
        $normalized = KeyNormalizer::normalize($field);
        foreach ($this->rules as $rule) {
            if (KeyNormalizer::normalize($rule->field()) === $normalized) {
                return $rule;
            }
        }

        return null;
    }

    public function matches(RuleDefinition $rule): bool
    {
        if (!$this->match) {
            return true;
        }
        $condition = $rule->condition();

        return !$condition instanceof ConditionDefinition || $condition->matches($this->snapshot, $this->normalizeKeys);
    }
}
