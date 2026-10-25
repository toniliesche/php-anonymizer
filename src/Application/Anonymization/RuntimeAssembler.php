<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;

/**
 * @internal
 */
final class RuntimeAssembler
{
    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public function assemble(
        ProfileProviderInterface $profiles,
        iterable $strategies = [],
        iterable $codecs = [],
        ?AnonymizationLimits $limits = null,
        iterable $transformations = [],
    ): AnonymizerRuntime {
        $limits ??= new AnonymizationLimits();
        $profileRegistry = new ProfileRegistry($profiles->profiles());

        // @infection-ignore-next-line Registries consume codec values and never expose iterable keys.
        /** @var list<PayloadCodecInterface> $registeredCodecs */
        $registeredCodecs = [new JsonPayloadCodec(), ...iterator_to_array($codecs, false)];
        $codecRegistry = new PayloadCodecRegistry($registeredCodecs);

        // @infection-ignore-next-line Registries consume strategy values and never expose iterable keys.
        /** @var list<ReplacementStrategyInterface> $registeredStrategies */
        $registeredStrategies = [new DefaultRedactionStrategy(), new StarMaskStrategy(), ...iterator_to_array($strategies, false)];
        $strategyRegistry = new ReplacementStrategyRegistry($registeredStrategies);
        $transformationRegistry = new TransformationRegistry([...CoreTransformationFactory::defaults(), ...iterator_to_array($transformations, false)]);

        new ProfileReferenceValidator()->validate($profileRegistry, $strategyRegistry, $codecRegistry, $transformationRegistry);
        $traversal = new ArrayTraversal($profileRegistry, $strategyRegistry, $codecRegistry, $transformationRegistry, $limits);

        return new AnonymizerRuntime(
            $profileRegistry,
            $strategyRegistry,
            $codecRegistry,
            $transformationRegistry,
            $traversal,
            new NdjsonAnonymizationService($profileRegistry, $traversal, $limits),
            new PayloadAnonymizationService($codecRegistry, $profileRegistry, $traversal, $limits),
            $limits,
        );
    }
}
