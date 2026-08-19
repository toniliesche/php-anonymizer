<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/** Creates the default array anonymizer without exposing its wiring details. */
final class AnonymizerFactory
{
    /**
     * @param iterable<ProfileDefinition> $profiles
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public static function fromProfiles(iterable $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null): AnonymizerInterface
    {
        return new ArrayAnonymizer(new ProfileCollection(iterator_to_array($profiles, false)), $strategies, $codecs, $limits);
    }

    /**
     * @param array<string, array<string, mixed>> $profiles
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public static function fromArrays(array $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null): AnonymizerInterface
    {
        $definitions = [];
        foreach ($profiles as $name => $definition) {
            $definitions[] = ProfileDefinition::fromArray($name, $definition);
        }

        return self::fromProfiles($definitions, $strategies, $codecs, $limits);
    }
}
