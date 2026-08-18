<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Domain\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\ScalarAction;

final readonly class RedactArrayTraversal
{
    public function __construct(
        private ReplacementStrategyRegistry $strategies,
        private EmbeddedPayloadTraversal $embedded,
    ) {}

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    public function apply(string $profile, array &$data, array $rules, array &$anonymizedPaths, array &$missingPaths): void
    {
        $this->rules($profile, $data, $rules, '', $anonymizedPaths, $missingPaths);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function rules(string $profile, array &$data, array $rules, string $parentPath, array &$anonymizedPaths, array &$missingPaths): void
    {
        $context = $data;
        foreach ($rules as $rule) {
            $this->rule($profile, $data, $context, $rule, $parentPath, $anonymizedPaths, $missingPaths);
        }
    }

    /**
     * @param array<mixed, mixed> $data
     * @param array<mixed, mixed> $context
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function rule(string $profile, array &$data, array $context, RuleDefinition $rule, string $parentPath, array &$anonymizedPaths, array &$missingPaths): void
    {
        $condition = $rule->condition();
        if ($condition instanceof ConditionDefinition && !$condition->matches($context)) {
            return;
        }
        $field = $rule->field();
        $path = $parentPath . '/' . $this->escape($field);
        if (!array_key_exists($field, $data)) {
            $missingPaths[] = $path;
            return;
        }
        $value = &$data[$field];
        if ($rule->embedded() instanceof EmbeddedPayloadDefinition) {
            $this->embedded->apply($profile, $value, $path, $rule->embedded()->codec(), $rule->embedded()->profile(), $anonymizedPaths, $missingPaths);
            return;
        }
        if ($rule->scalarAction() instanceof ScalarAction && !is_array($value)) {
            $this->scalar($profile, $value, $path, $rule->scalarAction(), $anonymizedPaths);
            return;
        }
        $this->traverseValue($profile, $value, $rule, $path, $anonymizedPaths, $missingPaths);
    }

    /**
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function traverseValue(string $profile, mixed &$value, RuleDefinition $rule, string $path, array &$anonymizedPaths, array &$missingPaths): void
    {
        if ($rule->hasChildren()) {
            if (!$this->isMap($value)) {
                throw UnsupportedValue::at($profile, $path, $value);
            }
            /** @var array<mixed, mixed> $value */
            $this->rules($profile, $value, $rule->children(), $path, $anonymizedPaths, $missingPaths);
            return;
        }
        if ($rule->eachStrings()) {
            $this->stringList($profile, $value, $path, $anonymizedPaths);
            return;
        }
        if ($rule->eachRules() !== null) {
            $this->mapList($profile, $value, $rule->eachRules(), $path, $anonymizedPaths, $missingPaths);
            return;
        }
        $this->string($profile, $value, $path, $rule->strategy(), $anonymizedPaths);
    }

    /** @param list<string> $anonymizedPaths */
    private function scalar(string $profile, mixed &$value, string $path, ScalarAction $action, array &$anonymizedPaths): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        if ($action === ScalarAction::REDACT) {
            $this->replace($value, $path, 'redact', $anonymizedPaths);
        }
    }

    /** @param list<string> $anonymizedPaths */
    private function stringList(string $profile, mixed &$value, string $path, array &$anonymizedPaths): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        foreach ($value as $index => &$item) {
            $this->string($profile, $item, $path . '/' . $index, null, $anonymizedPaths);
        }
        unset($item);
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function mapList(string $profile, mixed &$value, array $rules, string $path, array &$anonymizedPaths, array &$missingPaths): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        foreach ($value as $index => &$item) {
            if (!$this->isMap($item)) {
                throw UnsupportedValue::at($profile, $path . '/' . $index, $item);
            }
            /** @var array<mixed, mixed> $item */
            $this->rules($profile, $item, $rules, $path . '/' . $index, $anonymizedPaths, $missingPaths);
        }
        unset($item);
    }

    /** @param list<string> $anonymizedPaths */
    private function string(string $profile, mixed &$value, string $path, ?string $strategy, array &$anonymizedPaths): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        $this->replace($value, $path, $strategy ?? 'redact', $anonymizedPaths);
    }

    /** @param list<string> $anonymizedPaths */
    private function replace(string &$value, string $path, string $strategyId, array &$anonymizedPaths): void
    {
        $strategy = $this->strategies->get($strategyId);
        if (!$strategy instanceof ReplacementStrategyInterface) {
            throw new UnsupportedValue(sprintf('Replacement strategy "%s" is not available.', $strategyId));
        }
        $value = $strategy->replace($value);
        $anonymizedPaths[] = $path;
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
