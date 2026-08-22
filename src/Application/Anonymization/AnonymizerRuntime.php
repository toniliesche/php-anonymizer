<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;

/**
 * @internal builder-produced engine configuration
 *
 * @codeCoverageIgnore Immutable wiring value.
 */
final readonly class AnonymizerRuntime
{
    /**
     * @param list<ReplacementStrategyInterface> $strategies
     * @param list<PayloadCodecInterface> $codecs
     * @param list<ValueTransformationInterface> $transformations
     */
    public function __construct(
        public ProfileProviderInterface $profiles,
        public array $strategies = [],
        public array $codecs = [],
        public ?AnonymizationLimits $limits = null,
        public array $transformations = [],
    ) {
    }
}
