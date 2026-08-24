<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use LogicException;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

/**
 * @internal selected-rule dispatch
 */
final readonly class SelectedValueDispatcher
{
    public function __construct(private EmbeddedPayloadTraversal $embedded, private RuleTreeTraversal $tree)
    {
    }

    public function dispatch(mixed &$value, SelectedRule $selected, TraversalExecutionContext $context): void
    {
        $rule = $selected->rule;
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
            // @codeCoverageIgnoreStart CompiledRuleList is guaranteed by compilation.
            if (!$selected->nested instanceof CompiledRuleList) {
                // @codeCoverageIgnoreStart Defensive precompiled graph violation.
                throw new LogicException('Compiled child rules are missing.'); // @codeCoverageIgnore
                // @codeCoverageIgnoreEnd
            }
            // @codeCoverageIgnoreEnd
            $this->tree->dispatchChildren($value, $selected->nested, $context);

            return;
        }
        if ($rule->eachStrings()) {
            $this->tree->dispatchStrings($value, $context);

            return;
        }
        if ($rule->eachRules() !== null) {
            // @codeCoverageIgnoreStart CompiledRuleList is guaranteed by compilation.
            if (!$selected->nested instanceof CompiledRuleList) {
                throw new LogicException('Compiled each rules are missing.');
            }
            // @codeCoverageIgnoreEnd
            $this->tree->dispatchEach($value, $selected->nested, $context);

            return;
        }
        $context->policy->selected($context->profile, $value, $rule, $context->pointer, $context->report, $context->anonymizationContext);
    }
}
