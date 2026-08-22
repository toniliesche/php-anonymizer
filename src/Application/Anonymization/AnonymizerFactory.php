<?php

declare(strict_types=1);

/** @codeCoverageIgnoreFile Transitional internal construction helper. */

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/**
 * @internal transitional v1 construction helper; use AnonymizerBuilder
 *
 * @codeCoverageIgnore Transitional compatibility helper.
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
        $builder = AnonymizerBuilder::create()
            ->withProfiles($profiles)
            ->withReplacementStrategies($strategies)
            ->withPayloadCodecs($codecs)
            ->withValueTransformations($transformations);
        if ($limits instanceof AnonymizationLimits) {
            $builder = $builder->withLimits($limits);
        }

        return $builder->build();
    }

    /**
     * @param iterable<mixed> $providers
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public static function fromProfileProviders(iterable $providers, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null, iterable $transformations = []): AnonymizerInterface
    {
        $profiles = [];
        foreach ($providers as $provider) {
            if (!$provider instanceof ProfileProviderInterface) {
                throw new InvalidArgumentException(sprintf('Profile provider must implement %s, got %s.', ProfileProviderInterface::class, get_debug_type($provider)));
            }

            foreach ($provider->profiles() as $profile) {
                $profiles[] = $profile;
            }
        }

        return self::fromProfiles($profiles, $strategies, $codecs, $limits, $transformations);
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
