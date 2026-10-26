<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal map-snapshot rule selection
 */
final class RuleSelector
{
    /** @var array<int, bool> */
    private array $conditionResults = [];

    /**
     * @param array<mixed, mixed> $snapshot
     */
    public function __construct(
        private readonly CompiledRuleList $scoped,
        private readonly CompiledRuleList $global,
        private readonly array $snapshot,
        private readonly bool $normalizeKeys,
    ) {
    }

    public function select(string $field): ?SelectedRule
    {
        $rule = $this->scoped->resolve($field, $this->normalizeKeys);
        if ($rule instanceof RuleDefinition && $this->matches($rule)) {
            return new SelectedRule($rule, $this->scoped->nested($rule));
        }

        $rule = $this->global->resolve($field, $this->normalizeKeys);
        if ($rule instanceof RuleDefinition && $this->matches($rule)) {
            return new SelectedRule($rule, $this->global->nested($rule));
        }

        return null;
    }

    public function reportMissing(JsonPointer $pointer, AnonymizationReport $report): void
    {
        /** @var array<int, RuleDefinition> $present */
        $present = [];
        foreach (array_keys($this->snapshot) as $field) {
            $rule = $this->scoped->resolve((string) $field, $this->normalizeKeys);
            if ($rule instanceof RuleDefinition) {
                $present[spl_object_id($rule)] = $rule;
            }
        }

        foreach ($this->scoped->rules() as $rule) {
            if ($this->matches($rule) && !isset($present[spl_object_id($rule)])) {
                $report->missing($pointer->append($rule->field()));
            }
        }
    }

    private function matches(RuleDefinition $rule): bool
    {
        $condition = $rule->condition();
        if (!$condition instanceof ConditionDefinition) {
            return true;
        }

        return $this->conditionResults[spl_object_id($rule)] ??= $condition->matches($this->snapshot, $this->normalizeKeys);
    }
}
