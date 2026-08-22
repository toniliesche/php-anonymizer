<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal recursive rule-tree coordinator
 */
final readonly class RuleTreeTraversal
{
    private SelectedValueDispatcher $dispatcher;

    public function __construct(EmbeddedPayloadTraversal $embedded)
    {
        $this->dispatcher = new SelectedValueDispatcher($embedded, $this);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function apply(array &$data, TraversalExecutionContext $context): void
    {
        $this->map($data, $context);
    }

    /**
     * @param list<RuleDefinition> $rules
     */
    public function dispatchChildren(mixed &$value, array $rules, TraversalExecutionContext $context): void
    {
        if (!$this->isMap($value)) {
            throw UnsupportedValue::at($context->profile, (string) $context->pointer, $value);
        }

        /** @var array<mixed, mixed> $value */
        $this->map($value, $context->descend($context->pointer, $context->depth + 1, $rules));
    }

    public function dispatchStrings(mixed &$value, TraversalExecutionContext $context): void
    {
        if (!is_array($value)) {
            throw UnsupportedValue::at($context->profile, (string) $context->pointer, $value);
        }
        $context->budget->collection($context->profile, $context->pointer, count($value));
        $context->policy->strings($context->profile, $value, $context->pointer, $context->report, $context->anonymizationContext);
    }

    /**
     * @param list<RuleDefinition> $rules
     */
    public function dispatchEach(mixed &$value, array $rules, TraversalExecutionContext $context): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($context->profile, (string) $context->pointer, $value);
        }
        $context->budget->collection($context->profile, $context->pointer, count($value));
        foreach ($value as $index => &$item) {
            if (!$this->isMap($item)) {
                throw UnsupportedValue::at($context->profile, (string) $context->pointer->append($index), $item);
            }

            /** @var array<mixed, mixed> $item */
            $this->map($item, $context->descend($context->pointer->append($index), $context->depth + 1, $rules));
        }
        unset($item);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function map(array &$data, TraversalExecutionContext $context): void
    {
        $context->budget->visit($context->profile, $context->pointer, $context->depth);
        $context->budget->collection($context->profile, $context->pointer, count($data));
        $snapshot = $data;
        $scoped = new RuleIndex($context->rules, $snapshot, $context->normalizeKeys);
        $global = new RuleIndex($context->globalRules, $snapshot, $context->normalizeKeys, false);
        foreach ($context->rules as $rule) {
            if ($scoped->matches($rule) && !array_key_exists($rule->field(), $data)) {
                $context->report->missing($context->pointer->append($rule->field()));
            }
        }

        foreach ($data as $field => &$value) {
            $field = (string) $field;
            $path = $context->pointer->append($field);
            $rule = $scoped->resolve($field);
            if ($rule instanceof RuleDefinition && $scoped->matches($rule)) {
                $context->budget->visit($context->profile, $path, $context->depth + 1);
                $this->dispatcher->dispatch($value, $rule, $context->descend($path, $context->depth + 1));

                continue;
            }
            $globalRule = $global->resolve($field);
            if ($globalRule instanceof RuleDefinition && $global->matches($globalRule)) {
                $context->budget->visit($context->profile, $path, $context->depth + 1);
                $this->dispatcher->dispatch($value, $globalRule, $context->descend($path, $context->depth + 1));

                continue;
            }
            $this->unselected($value, $context->descend($path, $context->depth + 1));
        }
        unset($value);
    }

    private function unselected(mixed &$value, TraversalExecutionContext $context): void
    {
        if (is_array($value) && (!array_is_list($value) || $value === [])) {
            /** @var array<mixed, mixed> $value */
            $this->map($value, $context->descend($context->pointer, $context->depth, []));

            return;
        }
        if (is_array($value)) {
            foreach ($value as $index => &$item) {
                $this->unselected($item, $context->descend($context->pointer->append($index), $context->depth));
            }
            unset($item);

            return;
        }
        $context->policy->unselected($value, $context->pointer, $context->report, $context->anonymizationContext);
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && (!array_is_list($value) || $value === []);
    }
}

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
