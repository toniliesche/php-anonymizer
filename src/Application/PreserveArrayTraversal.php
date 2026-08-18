<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Domain\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\ScalarAction;

final readonly class PreserveArrayTraversal
{
    private RedactUnselectedValues $unselectedValues;

    public function __construct()
    {
        $this->unselectedValues = new RedactUnselectedValues();
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    public function apply(string $profile, array &$data, array $rules, array &$anonymizedPaths, array &$missingPaths): void
    {
        $this->map($profile, $data, $rules, '', $anonymizedPaths, $missingPaths);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function map(string $profile, array &$data, array $rules, string $parentPath, array &$anonymizedPaths, array &$missingPaths): void
    {
        $context = $data;
        $rulesByField = [];
        foreach ($rules as $rule) {
            $rulesByField[$rule->field()] = $rule;
            if ($this->matches($rule, $context) && !array_key_exists($rule->field(), $data)) {
                $missingPaths[] = $parentPath . '/' . $this->escape($rule->field());
            }
        }

        foreach ($data as $field => &$value) {
            $rule = $rulesByField[(string) $field] ?? null;
            $path = $parentPath . '/' . $this->escape((string) $field);
            if (!$rule instanceof RuleDefinition || !$this->matches($rule, $context)) {
                $this->unselectedValues->apply($value, $path, $anonymizedPaths);
                continue;
            }
            $this->selected($profile, $value, $rule, $path, $anonymizedPaths, $missingPaths);
        }
        unset($value);
    }

    /**
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function selected(string $profile, mixed &$value, RuleDefinition $rule, string $path, array &$anonymizedPaths, array &$missingPaths): void
    {
        if (is_string($value)) {
            $this->selectedString($value, $rule, $path, $profile, $anonymizedPaths);
            return;
        }
        if (!is_array($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        if ($rule->hasChildren()) {
            $this->selectedMap($profile, $value, $rule->children(), $path, $anonymizedPaths, $missingPaths);
            return;
        }
        if ($rule->eachStrings()) {
            $this->selectedStrings($profile, $value, $path);
            return;
        }
        if ($rule->eachRules() !== null) {
            $this->selectedMaps($profile, $value, $rule->eachRules(), $path, $anonymizedPaths, $missingPaths);
            return;
        }
        throw UnsupportedValue::at($profile, $path, $value);
    }

    /** @param list<string> $anonymizedPaths */
    private function selectedString(mixed &$value, RuleDefinition $rule, string $path, string $profile, array &$anonymizedPaths): void
    {
        if ($rule->scalarAction() === ScalarAction::REDACT) {
            $value = '[REDACTED]';
            $anonymizedPaths[] = $path;
            return;
        }
        if (!$rule->scalarAction() instanceof ScalarAction && ($rule->hasChildren() || $rule->eachStrings() || $rule->eachRules() !== null)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
    }

    /**
     * @param array<mixed, mixed> $value
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function selectedMap(string $profile, array &$value, array $rules, string $path, array &$anonymizedPaths, array &$missingPaths): void
    {
        if (!$this->isMap($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        $this->map($profile, $value, $rules, $path, $anonymizedPaths, $missingPaths);
    }

    /** @param array<mixed, mixed> $value */
    private function selectedStrings(string $profile, array $value, string $path): void
    {
        if (!array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        foreach ($value as $index => $item) {
            if (!is_string($item)) {
                throw UnsupportedValue::at($profile, $path . '/' . $index, $item);
            }
        }
    }

    /**
     * @param array<mixed, mixed> $value
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function selectedMaps(string $profile, array &$value, array $rules, string $path, array &$anonymizedPaths, array &$missingPaths): void
    {
        if (!array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        foreach ($value as $index => &$item) {
            if (!$this->isMap($item)) {
                throw UnsupportedValue::at($profile, $path . '/' . $index, $item);
            }
            /** @var array<mixed, mixed> $item */
            $this->map($profile, $item, $rules, $path . '/' . $index, $anonymizedPaths, $missingPaths);
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

    private function escape(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
