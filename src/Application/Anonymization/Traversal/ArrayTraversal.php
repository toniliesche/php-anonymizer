<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMode;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;

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
        ?TransformationRegistry $transformations = null,
        private AnonymizationLimits $limits = new AnonymizationLimits(),
    ) {
        $transformations ??= new TransformationRegistry(CoreTransformationFactory::defaults());
        $this->embedded = new EmbeddedPayloadTraversal($codecs, $this);
        $this->rules = new RuleTreeTraversal($this->embedded);
        $this->redactor = new RedactTraversalPolicy($strategies, $transformations);
        $this->preserver = new PreserveTraversalPolicy($strategies, $transformations);
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<mixed, mixed> $data
     */
    // @phpstan-ignore complexity.functionLike (document root dispatch is intentionally explicit)
    public function applyDocument(string $profile, array $data, ?AnonymizationContext $context = null): DocumentAnonymizationResult
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }

        $report = AnonymizationReport::collecting();
        $budget = new ExecutionBudget($this->limits);
        if ($definition->root() === ProfileRoot::MAP) {
            if (!$this->isMap($data)) {
                throw new InvalidArgumentException(sprintf('Profile "%s" requires a map document root.', $profile));
            }
            $this->applyMap($profile, $data, $definition, $report, $budget, $context);
        } else {
            if (!array_is_list($data)) {
                throw new InvalidArgumentException(sprintf('Profile "%s" requires a list document root.', $profile));
            }
            $budget->collection($profile, JsonPointer::root(), count($data));
            foreach ($data as $index => &$item) {
                if (!$this->isMap($item) || !is_array($item)) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" requires list items to be associative maps at /%s.', $profile, (string) $index));
                }

                /** @var array<mixed, mixed> $item */
                $this->applyMap($profile, $item, $definition, $report, $budget, $context, JsonPointer::root()->append((string) $index));
            }
            unset($item);
        }

        return new DocumentAnonymizationResult($data, $report->anonymizedPaths(), $report->missingPaths(), $report->events());
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function applyMap(string $profile, array &$data, ProfileDefinition $definition, AnonymizationReport $report, ExecutionBudget $budget, ?AnonymizationContext $context, ?JsonPointer $parent = null): void
    {
        $policy = $definition->mode() === ProfileMode::PRESERVE ? $this->preserver : $this->redactor;
        $this->rules->apply($profile, $data, $definition->rules(), $policy, $report, $budget, $context, $parent);
    }

    private function isMap(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return !array_is_list($value) || $value === [];
    }
}
