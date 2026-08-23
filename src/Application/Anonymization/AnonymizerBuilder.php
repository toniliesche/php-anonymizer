<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Extension\AnonymizerExtensionInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/**
 * @codeCoverageIgnore Builder wiring is exercised through the public façade contract.
 *
 * Immutable fluent construction API for the configured anonymizer façade.
 */
final readonly class AnonymizerBuilder
{
    /**
     * @param list<ProfileDefinition> $profiles
     * @param list<ProfileProviderInterface> $providers
     * @param list<ReplacementStrategyInterface> $strategies
     * @param list<PayloadCodecInterface> $codecs
     * @param list<ValueTransformationInterface> $transformations
     */
    public function __construct(
        private array $profiles = [],
        private array $providers = [],
        private array $strategies = [],
        private array $codecs = [],
        private ?AnonymizationLimits $limits = null,
        private array $transformations = [],
        private ?ObjectAnonymizerInterface $objectAnonymizer = null,
    ) {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withProfile(ProfileDefinition $profile): self
    {
        $profiles = [...$this->profiles, $profile];

        return new self($profiles, $this->providers, $this->strategies, $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    /**
     * @param iterable<ProfileDefinition> $profiles
     */
    public function withProfiles(iterable $profiles): self
    {
        return new self([...$this->profiles, ...iterator_to_array($profiles, false)], $this->providers, $this->strategies, $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    public function withProfileProvider(ProfileProviderInterface $provider): self
    {
        return new self($this->profiles, [...$this->providers, $provider], $this->strategies, $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    /**
     * @param iterable<mixed> $providers
     */
    public function withProfileProviders(iterable $providers): self
    {
        /** @var list<mixed> $registered */
        $registered = iterator_to_array($providers, false);

        /** @var list<ProfileProviderInterface> $validated */
        $validated = [];
        foreach ($registered as $provider) {
            if (!$provider instanceof ProfileProviderInterface) {
                throw new InvalidArgumentException(sprintf('Profile provider must implement %s, got %s.', ProfileProviderInterface::class, get_debug_type($provider)));
            }
            $validated[] = $provider;
        }

        return new self($this->profiles, [...$this->providers, ...$validated], $this->strategies, $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    public function withReplacementStrategy(ReplacementStrategyInterface $strategy): self
    {
        return new self($this->profiles, $this->providers, [...$this->strategies, $strategy], $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     */
    public function withReplacementStrategies(iterable $strategies): self
    {
        return new self($this->profiles, $this->providers, [...$this->strategies, ...iterator_to_array($strategies, false)], $this->codecs, $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    public function withPayloadCodec(PayloadCodecInterface $codec): self
    {
        return new self($this->profiles, $this->providers, $this->strategies, [...$this->codecs, $codec], $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    /**
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public function withPayloadCodecs(iterable $codecs): self
    {
        return new self($this->profiles, $this->providers, $this->strategies, [...$this->codecs, ...iterator_to_array($codecs, false)], $this->limits, $this->transformations, $this->objectAnonymizer);
    }

    public function withValueTransformation(ValueTransformationInterface $transformation): self
    {
        return new self($this->profiles, $this->providers, $this->strategies, $this->codecs, $this->limits, [...$this->transformations, $transformation], $this->objectAnonymizer);
    }

    /**
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public function withValueTransformations(iterable $transformations): self
    {
        return new self($this->profiles, $this->providers, $this->strategies, $this->codecs, $this->limits, [...$this->transformations, ...iterator_to_array($transformations, false)], $this->objectAnonymizer);
    }

    public function withLimits(AnonymizationLimits $limits): self
    {
        return new self($this->profiles, $this->providers, $this->strategies, $this->codecs, $limits, $this->transformations, $this->objectAnonymizer);
    }

    public function withObjectAnonymizer(ObjectAnonymizerInterface $objectAnonymizer): self
    {
        if ($this->objectAnonymizer instanceof ObjectAnonymizerInterface) {
            throw new InvalidArgumentException('Only one object anonymizer may be registered.');
        }

        return new self($this->profiles, $this->providers, $this->strategies, $this->codecs, $this->limits, $this->transformations, $objectAnonymizer);
    }

    public function withExtension(AnonymizerExtensionInterface $extension): self
    {
        return $extension->configure($this);
    }

    public function build(): AnonymizerInterface
    {
        $profiles = $this->profiles;
        foreach ($this->providers as $provider) {
            foreach ($provider->profiles() as $profile) {
                $profiles[] = $profile;
            }
        }

        $runtime = new RuntimeAssembler()->assemble(
            new ProfileCollection($profiles),
            $this->strategies,
            $this->codecs,
            $this->limits,
            $this->transformations,
        );

        return new AnonymizerFacade($runtime, $this->objectAnonymizer);
    }
}
