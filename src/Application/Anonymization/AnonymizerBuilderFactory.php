<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Extension\AnonymizerExtensionInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;

/**
 * @internal framework construction helper for immutable builders
 */
final class AnonymizerBuilderFactory
{
    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     * @param iterable<AnonymizerExtensionInterface> $extensions
     */
    public static function create(
        ProfileProviderInterface $profiles,
        iterable $strategies,
        iterable $codecs,
        AnonymizationLimits $limits,
        iterable $transformations,
        ?ObjectAnonymizerInterface $objectAnonymizer = null,
        iterable $extensions = [],
    ): AnonymizerBuilder {
        $builder = AnonymizerBuilder::create()
            ->withProfileProvider($profiles)
            ->withReplacementStrategies($strategies)
            ->withPayloadCodecs($codecs)
            ->withValueTransformations($transformations)
            ->withLimits($limits);

        foreach ($extensions as $extension) {
            $builder = $builder->withExtension($extension);
        }

        return $objectAnonymizer instanceof ObjectAnonymizerInterface
            ? $builder->withObjectAnonymizer($objectAnonymizer)
            : $builder;
    }
}
