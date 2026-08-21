<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RuleTraversalPolicyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

// @phpstan-ignore complexity.classLike (global and scoped traversal share one bounded pipeline)
final readonly class RuleTreeTraversal
{
    public function __construct(private EmbeddedPayloadTraversal $embedded)
    {
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     */
    public function apply(string $profile, array &$data, array $rules, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, ?AnonymizationContext $context = null, ?JsonPointer $parent = null, array $globalRules = [], bool $normalizeKeys = false): void
    {
        $this->map($profile, $data, $rules, $globalRules, $parent ?? JsonPointer::root(), $policy, $report, $budget, 0, $context, $normalizeKeys);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     */
    // @phpstan-ignore complexity.functionLike (rule precedence and recursive global traversal are explicit)
    private function map(string $profile, array &$data, array $rules, array $globalRules, JsonPointer $parent, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, int $depth, ?AnonymizationContext $context, bool $normalizeKeys): void
    {
        $budget->visit($profile, $parent, $depth);
        $budget->collection($profile, $parent, count($data));
        $snapshot = $data;

        /** @var array<string, RuleDefinition> $rulesByField */
        $rulesByField = [];

        /** @var array<string, list<RuleDefinition>> $normalizedRules */
        $normalizedRules = [];

        /** @var array<string, bool> $matchesByField */
        $matchesByField = [];
        foreach ($rules as $rule) {
            $rulesByField[$rule->field()] = $rule;
            if ($normalizeKeys) {
                $normalizedRules[KeyNormalizer::normalize($rule->field())][] = $rule;
            }
            $matchesByField[$rule->field()] = $this->matches($rule, $snapshot, $normalizeKeys);
            if ($matchesByField[$rule->field()] && !array_key_exists($rule->field(), $data)) {
                $report->missing($parent->append($rule->field()));
            }
        }

        /** @var array<string, list<RuleDefinition>> $normalizedGlobalRules */
        $normalizedGlobalRules = [];

        /** @var array<string, RuleDefinition> $globalRulesByField */
        $globalRulesByField = [];
        foreach ($globalRules as $rule) {
            $globalRulesByField[$rule->field()] = $rule;
            if ($normalizeKeys) {
                $normalizedGlobalRules[KeyNormalizer::normalize($rule->field())][] = $rule;
            }
        }
        foreach ($data as $field => &$value) {
            $field = (string) $field;
            $rule = $this->resolve($field, $rulesByField, $normalizedRules, $normalizeKeys);
            $path = $parent->append($field);
            if ($rule instanceof RuleDefinition && $matchesByField[$rule->field()]) {
                $budget->visit($profile, $path, $depth + 1);
                $this->selected($profile, $value, $rule, $path, $policy, $report, $budget, $depth + 1, $context, $globalRules, $normalizeKeys);

                continue;
            }
            $globalRule = $this->resolve($field, $globalRulesByField, $normalizedGlobalRules, $normalizeKeys);
            if ($globalRule instanceof RuleDefinition && $this->matches($globalRule, $snapshot, $normalizeKeys)) {
                $budget->visit($profile, $path, $depth + 1);
                $this->selected($profile, $value, $globalRule, $path, $policy, $report, $budget, $depth + 1, $context, $globalRules, $normalizeKeys);

                continue;
            }
            $this->unselected($profile, $value, $path, $globalRules, $policy, $report, $budget, $depth + 1, $context, $normalizeKeys);
        }
        unset($value);
    }

    /**
     * @param list<RuleDefinition> $globalRules
     */
    private function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, int $depth, ?AnonymizationContext $context, array $globalRules, bool $normalizeKeys): void
    {
        if ($rule->embedded() instanceof EmbeddedPayloadDefinition) {
            $this->embedded->apply($profile, $value, (string) $path, $rule->embedded()->codec(), $rule->embedded()->profile(), $report, $budget, $context);

            return;
        }
        if ($rule->scalarAction() instanceof ScalarAction && !is_array($value)) {
            $policy->scalar($profile, $value, $rule->scalarAction(), $path, $report, $context);

            return;
        }
        if ($rule->hasChildren()) {
            $this->children($profile, $value, $rule->children(), $path, $policy, $report, $budget, $depth, $context, $globalRules, $normalizeKeys);

            return;
        }
        if ($rule->eachStrings()) {
            $this->strings($profile, $value, $path, $policy, $report, $budget, $context);

            return;
        }
        if ($rule->eachRules() !== null) {
            $this->each($profile, $value, $rule->eachRules(), $path, $policy, $report, $budget, $depth, $context, $globalRules, $normalizeKeys);

            return;
        }
        $policy->selected($profile, $value, $rule, $path, $report, $context);
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     */
    private function children(string $profile, mixed &$value, array $rules, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, int $depth, ?AnonymizationContext $context, array $globalRules, bool $normalizeKeys): void
    {
        if (!$this->isMap($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }

        /** @var array<mixed, mixed> $value */
        $this->map($profile, $value, $rules, $globalRules, $path, $policy, $report, $budget, $depth + 1, $context, $normalizeKeys);
    }

    private function strings(string $profile, mixed &$value, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, ?AnonymizationContext $context): void
    {
        if (!is_array($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $budget->collection($profile, $path, count($value));
        $policy->strings($profile, $value, $path, $report, $context);
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     */
    private function each(string $profile, mixed &$value, array $rules, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, int $depth, ?AnonymizationContext $context, array $globalRules, bool $normalizeKeys): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $budget->collection($profile, $path, count($value));
        foreach ($value as $index => &$item) {
            if (!$this->isMap($item)) {
                throw UnsupportedValue::at($profile, (string) $path->append($index), $item);
            }

            /** @var array<mixed, mixed> $item */
            $this->map($profile, $item, $rules, $globalRules, $path->append($index), $policy, $report, $budget, $depth + 1, $context, $normalizeKeys);
        }
        unset($item);
    }

    /**
     * @param array<mixed, mixed> $context
     */
    private function matches(RuleDefinition $rule, array $context, bool $normalizeKeys): bool
    {
        $condition = $rule->condition();

        return !$condition instanceof ConditionDefinition || $condition->matches($context, $normalizeKeys);
    }

    /**
     * @param array<string, RuleDefinition> $rulesByField
     * @param array<string, list<RuleDefinition>> $normalizedRules
     */
    private function resolve(string $field, array $rulesByField, array $normalizedRules, bool $normalizeKeys): ?RuleDefinition
    {
        if (isset($rulesByField[$field])) {
            return $rulesByField[$field];
        }
        if (!$normalizeKeys) {
            return null;
        }

        return $normalizedRules[KeyNormalizer::normalize($field)][0] ?? null;
    }

    /**
     * @param list<RuleDefinition> $globalRules
     */
    private function unselected(string $profile, mixed &$value, JsonPointer $path, array $globalRules, RuleTraversalPolicyInterface $policy, AnonymizationReport $report, ExecutionBudget $budget, int $depth, ?AnonymizationContext $context, bool $normalizeKeys): void
    {
        if (is_array($value) && (!array_is_list($value) || $value === [])) {
            /** @var array<mixed, mixed> $value */
            $this->map($profile, $value, [], $globalRules, $path, $policy, $report, $budget, $depth, $context, $normalizeKeys);

            return;
        }
        if (is_array($value)) {
            foreach ($value as $index => &$item) {
                $this->unselected($profile, $item, $path->append($index), $globalRules, $policy, $report, $budget, $depth, $context, $normalizeKeys);
            }
            unset($item);

            return;
        }
        $policy->unselected($value, $path, $report, $context);
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && (!array_is_list($value) || $value === []);
    }
}
