<?php

declare(strict_types=1);

/** @codeCoverageIgnoreFile Internal traversal dispatch is covered through traversal contracts. */

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

/**
 * @internal selected-rule dispatch
 *
 * @codeCoverageIgnore Internal execution dispatcher.
 */
final readonly class SelectedValueDispatcher
{
    public function __construct(private EmbeddedPayloadTraversal $embedded, private RuleTreeTraversal $tree)
    {
    }

    public function dispatch(mixed &$value, RuleDefinition $rule, TraversalExecutionContext $context): void
    {
        if ($rule->embedded() instanceof EmbeddedPayloadDefinition) {
            $embedded = $rule->embedded();
            $this->embedded->apply($context->profile, $value, (string) $context->pointer, $embedded->codec(), $embedded->profile(), $context->report, $context->budget, $context->anonymizationContext);

            return;
        }
        if ($rule->scalarAction() instanceof ScalarAction && !is_array($value)) {
            $context->policy->scalar($context->profile, $value, $rule->scalarAction(), $context->pointer, $context->report, $context->anonymizationContext);

            return;
        }
        if ($rule->hasChildren()) {
            $this->tree->dispatchChildren($value, $rule->children(), $context);

            return;
        }
        if ($rule->eachStrings()) {
            $this->tree->dispatchStrings($value, $context);

            return;
        }
        if ($rule->eachRules() !== null) {
            $this->tree->dispatchEach($value, $rule->eachRules(), $context);

            return;
        }
        $context->policy->selected($context->profile, $value, $rule, $context->pointer, $context->report, $context->anonymizationContext);
    }
}
