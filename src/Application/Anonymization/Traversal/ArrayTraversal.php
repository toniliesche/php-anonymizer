<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMode;

final readonly class ArrayTraversal
{
    private EmbeddedPayloadTraversal $embedded;
    private RuleTreeTraversal $rules;
    private RedactTraversalPolicy $redactor;
    private PreserveTraversalPolicy $preserver;

    public function __construct(
        private ProfileRegistry $profiles,
        ReplacementStrategyRegistry $strategies,
        PayloadCodecRegistry $codecs,
        private AnonymizationLimits $limits = new AnonymizationLimits(),
    ) {
        $this->embedded = new EmbeddedPayloadTraversal($codecs, $this);
        $this->rules = new RuleTreeTraversal($this->embedded);
        $this->redactor = new RedactTraversalPolicy($strategies);
        $this->preserver = new PreserveTraversalPolicy($strategies);
    }

    /** @param array<string, mixed> $data */
    public function apply(string $profile, array $data, bool $withReport = true, ?ExecutionBudget $budget = null, ?AnonymizationContext $context = null): AnonymizationResult
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }

        /** @var array<string, mixed> $result */
        $result = $data;
        $report = $withReport ? AnonymizationReport::collecting() : AnonymizationReport::disabled();
        $budget ??= new ExecutionBudget($this->limits);
        if ($definition->mode() === ProfileMode::PRESERVE) {
            $this->rules->apply($profile, $result, $definition->rules(), $this->preserver, $report, $budget, $context);
        } else {
            $this->rules->apply($profile, $result, $definition->rules(), $this->redactor, $report, $budget, $context);
        }

        /** @var array<string, mixed> $result */
        return new AnonymizationResult($result, $report->anonymizedPaths(), $report->missingPaths(), $report->events());
    }
}
