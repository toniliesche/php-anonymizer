<?php

declare(strict_types=1);

/** @codeCoverageIgnoreFile Internal execution state is covered through traversal contracts. */

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RuleTraversalPolicyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal immutable state shared by one recursive traversal
 *
 * @codeCoverageIgnore Internal execution value.
 *
 * @phpstan-type RuleList list<RuleDefinition>
 */
final readonly class TraversalExecutionContext
{
    /**
     * @param RuleList $rules
     * @param RuleList $globalRules
     */
    public function __construct(
        public string $profile,
        public array $rules,
        public array $globalRules,
        public RuleTraversalPolicyInterface $policy,
        public AnonymizationReport $report,
        public ExecutionBudget $budget,
        public JsonPointer $pointer,
        public int $depth,
        public ?AnonymizationContext $anonymizationContext,
        public bool $normalizeKeys,
    ) {
    }

    /**
     * @param null|RuleList $rules
     */
    public function descend(JsonPointer $pointer, int $depth, ?array $rules = null): self
    {
        return new self(
            $this->profile,
            $rules ?? $this->rules,
            $this->globalRules,
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
