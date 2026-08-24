<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RuleTraversalPolicyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;

/**
 * @internal immutable state shared by one recursive traversal
 */
final readonly class TraversalExecutionContext
{
    public function __construct(
        public string $profile,
        public CompiledRuleList $rules,
        public CompiledRuleList $globalRules,
        public CompiledRuleList $emptyRules,
        public RuleTraversalPolicyInterface $policy,
        public AnonymizationReport $report,
        public ExecutionBudget $budget,
        public JsonPointer $pointer,
        public int $depth,
        public ?AnonymizationContext $anonymizationContext,
        public bool $normalizeKeys,
    ) {
    }

    public function descend(JsonPointer $pointer, int $depth, ?CompiledRuleList $rules = null): self
    {
        return new self(
            $this->profile,
            $rules ?? $this->rules,
            $this->globalRules,
            $this->emptyRules,
            $this->policy,
            $this->report,
            $this->budget,
            $pointer,
            $depth,
            $this->anonymizationContext,
            $this->normalizeKeys,
        );
    }
}
