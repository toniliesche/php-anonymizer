<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;

/**
 * @internal framework construction helper for immutable builders
 *
 * @codeCoverageIgnore Container-only wiring helper.
 */
final class AnonymizerBuilderFactory
{
    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public static function create(
        ProfileProviderInterface $profiles,
        iterable $strategies,
        iterable $codecs,
        AnonymizationLimits $limits,
        iterable $transformations,
        ?ObjectAnonymizerInterface $objectAnonymizer = null,
    ): AnonymizerBuilder {
        $builder = AnonymizerBuilder::create()
            ->withProfileProvider($profiles)
            ->withReplacementStrategies($strategies)
            ->withPayloadCodecs($codecs)
            ->withValueTransformations($transformations)
            ->withLimits($limits);

        return $objectAnonymizer instanceof ObjectAnonymizerInterface
            ? $builder->withObjectAnonymizer($objectAnonymizer)
            : $builder;
    }
}
