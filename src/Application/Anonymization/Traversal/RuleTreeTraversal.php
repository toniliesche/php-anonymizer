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
        $this->map($value, $context->descend($context->pointer, $context->depth, $rules, true));
    }

    public function dispatchStrings(mixed &$value, TraversalExecutionContext $context): void
    {
        if (!is_array($value)) {
            throw UnsupportedValue::at($context->profile, (string) $context->pointer, $value);
        }
        $context->budget->collection($context->profile, $context->pointer, count($value));
        foreach ($value as $index => &$item) {
            $this->visitNode($item, $context->descend($context->pointer->append($index), $context->depth + 1));
        }
        unset($item);
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
            $childContext = $context->descend($context->pointer->append($index), $context->depth + 1, $rules);
            $this->visitNode($item, $childContext);
            $this->map($item, $childContext->descend($childContext->pointer, $childContext->depth, $rules, true));
        }
        unset($item);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function map(array &$data, TraversalExecutionContext $context): void
    {
        if (!$context->currentNodeVisited) {
            $this->visitNode($data, $context);
        }
        $snapshot = $data;
        $selector = new RuleSelector($context->rules, $context->globalRules, $snapshot, $context->normalizeKeys);
        $selector->reportMissing($context->pointer, $context->report);

        foreach ($data as $field => &$value) {
            $field = (string) $field;
            $path = $context->pointer->append($field);
            $selected = $selector->select($field);
            $childContext = $context->descend($path, $context->depth + 1);
            $this->visitNode($value, $childContext);
            $childContext = $childContext->descend($path, $childContext->depth, currentNodeVisited: true);
            if ($selected instanceof SelectedRule) {
                $this->dispatcher->dispatch($value, $selected, $childContext);

                continue;
            }
            $this->unselected($value, $childContext);
        }
        unset($value);
    }

    private function unselected(mixed &$value, TraversalExecutionContext $context): void
    {
        if (is_array($value) && (!array_is_list($value) || $value === [])) {
            /** @var array<mixed, mixed> $value */
            $this->map($value, $context->descend($context->pointer, $context->depth, $context->emptyRules, true));

            return;
        }
        if (is_array($value)) {
            foreach ($value as $index => &$item) {
                $childContext = $context->descend($context->pointer->append($index), $context->depth + 1);
                $this->visitNode($item, $childContext);
                $this->unselected($item, $childContext->descend($childContext->pointer, $childContext->depth, currentNodeVisited: true));
            }
            unset($item);

            return;
        }
        $context->policy->unselected($value, $context->pointer, $context->report, $context->anonymizationContext);
    }

    private function visitNode(mixed $value, TraversalExecutionContext $context): void
    {
        $context->budget->visit($context->profile, $context->pointer, $context->depth);
        if (is_array($value)) {
            $context->budget->collection($context->profile, $context->pointer, count($value));
        }
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && (!array_is_list($value) || $value === []);
    }
}
