<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/**
 * Creates the default array anonymizer without exposing its wiring details.
 */
final class AnonymizerFactory
{
    /**
     * @param iterable<ProfileDefinition> $profiles
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public static function fromProfiles(iterable $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null, iterable $transformations = []): AnonymizerInterface
    {
        // @infection-ignore-next-line Preserving iterable keys has no effect on ProfileCollection's value iteration.
        return new ArrayAnonymizer(new ProfileCollection(iterator_to_array($profiles, false)), $strategies, $codecs, $limits, $transformations);
    }

    /**
     * @param array<string, array<string, mixed>> $profiles
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public static function fromArrays(array $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null, iterable $transformations = []): AnonymizerInterface
    {
        $definitions = [];
        foreach ($profiles as $name => $definition) {
            $definitions[] = ProfileDefinition::fromArray($name, $definition);
        }

        return self::fromProfiles($definitions, $strategies, $codecs, $limits, $transformations);
    }
}
