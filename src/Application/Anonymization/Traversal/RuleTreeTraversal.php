<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;

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

    public function dispatchChildren(mixed &$value, CompiledRuleList $rules, TraversalExecutionContext $context): void
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

    public function dispatchEach(mixed &$value, CompiledRuleList $rules, TraversalExecutionContext $context): void
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
        $selector = new RuleSelector($context->rules, $context->globalRules, $snapshot, $context->normalizeKeys);
        $selector->reportMissing($context->pointer, $context->report);

        foreach ($data as $field => &$value) {
            $field = (string) $field;
            $path = $context->pointer->append($field);
            $selected = $selector->select($field);
            if ($selected instanceof SelectedRule) {
                $context->budget->visit($context->profile, $path, $context->depth + 1);
                $this->dispatcher->dispatch($value, $selected, $context->descend($path, $context->depth + 1));

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
            $this->map($value, $context->descend($context->pointer, $context->depth, $context->emptyRules));

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
