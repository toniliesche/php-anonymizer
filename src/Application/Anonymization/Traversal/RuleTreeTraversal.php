<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RuleTraversalPolicyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

final readonly class RuleTreeTraversal
{
    public function __construct(private EmbeddedPayloadTraversal $embedded) {}

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     */
    public function apply(string $profile, array &$data, array $rules, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        $this->map($profile, $data, $rules, JsonPointer::root(), $policy, $report);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     */
    private function map(string $profile, array &$data, array $rules, JsonPointer $parent, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        $context = $data;
        $rulesByField = [];
        foreach ($rules as $rule) {
            $rulesByField[$rule->field()] = $rule;
            if ($this->matches($rule, $context) && !array_key_exists($rule->field(), $data)) {
                $report->missing($parent->append($rule->field()));
            }
        }
        foreach ($data as $field => &$value) {
            $rule = $rulesByField[(string) $field] ?? null;
            $path = $parent->append((string) $field);
            if (!$rule instanceof RuleDefinition || !$this->matches($rule, $context)) {
                $policy->unselected($value, $path, $report);
                continue;
            }
            $this->selected($profile, $value, $rule, $path, $policy, $report);
        }
        unset($value);
    }

    private function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        if ($rule->embedded() instanceof EmbeddedPayloadDefinition) {
            $this->embedded->apply($profile, $value, (string) $path, $rule->embedded()->codec(), $rule->embedded()->profile(), $report);
            return;
        }
        if ($rule->scalarAction() instanceof ScalarAction && !is_array($value)) {
            $policy->scalar($profile, $value, $rule->scalarAction(), $path, $report);
            return;
        }
        if ($rule->hasChildren()) {
            $this->children($profile, $value, $rule->children(), $path, $policy, $report);
            return;
        }
        if ($rule->eachStrings()) {
            $this->strings($profile, $value, $path, $policy, $report);
            return;
        }
        if ($rule->eachRules() !== null) {
            $this->each($profile, $value, $rule->eachRules(), $path, $policy, $report);
            return;
        }
        $policy->selected($profile, $value, $rule, $path, $report);
    }

    /** @param list<RuleDefinition> $rules */
    private function children(string $profile, mixed &$value, array $rules, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        if (!$this->isMap($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        /** @var array<mixed, mixed> $value */
        $this->map($profile, $value, $rules, $path, $policy, $report);
    }

    private function strings(string $profile, mixed &$value, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        if (!is_array($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $policy->strings($profile, $value, $path, $report);
    }

    /** @param list<RuleDefinition> $rules */
    private function each(string $profile, mixed &$value, array $rules, JsonPointer $path, RuleTraversalPolicyInterface $policy, AnonymizationReport $report): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        foreach ($value as $index => &$item) {
            if (!$this->isMap($item)) {
                throw UnsupportedValue::at($profile, (string) $path->append($index), $item);
            }
            /** @var array<mixed, mixed> $item */
            $this->map($profile, $item, $rules, $path->append($index), $policy, $report);
        }
        unset($item);
    }

    /** @param array<mixed, mixed> $context */
    private function matches(RuleDefinition $rule, array $context): bool
    {
        $condition = $rule->condition();
        return !$condition instanceof ConditionDefinition || $condition->matches($context);
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && (!array_is_list($value) || $value === []);
    }
}
