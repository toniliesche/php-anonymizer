<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;

/**
 * @internal assembled builder-produced runtime graph
 *
 * @codeCoverageIgnore Immutable wiring value.
 */
final readonly class AnonymizerRuntime
{
    public function __construct(
        public ProfileRegistry $profiles,
        public ReplacementStrategyRegistry $strategies,
        public PayloadCodecRegistry $codecs,
        public TransformationRegistry $transformations,
        public ArrayTraversal $traversal,
        public NdjsonAnonymizationService $ndjson,
        public PayloadAnonymizationService $payloads,
        public AnonymizationLimits $limits,
    ) {
    }
}
