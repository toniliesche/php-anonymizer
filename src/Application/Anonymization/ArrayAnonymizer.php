<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
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
use Throwable;

final readonly class ArrayAnonymizer implements AnonymizerInterface
{
    private ProfileRegistry $profileRegistry;
    private PayloadCodecRegistry $codecRegistry;
    private ArrayTraversal $traversal;

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public function __construct(ProfileProviderInterface $profiles, iterable $strategies = [], iterable $codecs = [])
    {
        $this->profileRegistry = new ProfileRegistry($profiles->profiles());
        /** @var list<PayloadCodecInterface> $registeredCodecs */
        $registeredCodecs = [new JsonPayloadCodec(), ...iterator_to_array($codecs, false)];
        $this->codecRegistry = new PayloadCodecRegistry($registeredCodecs);
        /** @var list<ReplacementStrategyInterface> $registeredStrategies */
        $registeredStrategies = [new DefaultRedactionStrategy(), new StarMaskStrategy(), ...iterator_to_array($strategies, false)];
        $strategyRegistry = new ReplacementStrategyRegistry($registeredStrategies);
        new ProfileReferenceValidator()->validate($this->profileRegistry, $strategyRegistry, $this->codecRegistry);
        $this->traversal = new ArrayTraversal($this->profileRegistry, $strategyRegistry, $this->codecRegistry);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array $data): array
    {
        return $this->anonymizeWithReport($profile, $data)->data();
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithReport(string $profile, array $data): AnonymizationResult
    {
        return $this->traversal->apply($profile, $data);
    }

    #[Override]
    public function anonymizePayload(string $profile, string $codec, string $payload): string
    {
        return $this->anonymizePayloadWithReport($profile, $codec, $payload)->payload();
    }

    #[Override]
    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload): PayloadAnonymizationResult
    {
        $payloadCodec = $this->codecRegistry->get($codec);
        if (!$payloadCodec instanceof PayloadCodecInterface) {
            throw new InvalidArgumentException(sprintf('Payload codec "%s" is not registered.', $codec));
        }

        try {
            $data = $payloadCodec->decode($payload);
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'decode', $exception);
        }
        if (!$this->isMap($data)) {
            throw PayloadCodecFailure::expectedMap($profile, $codec);
        }
        /** @var array<string, mixed> $data */

        $result = $this->anonymizeWithReport($profile, $data);
        try {
            $encoded = $payloadCodec->encode($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'encode', $exception);
        }

        return new PayloadAnonymizationResult($encoded, $result);
    }

    /** @param array<mixed, mixed> $value */
    private function isMap(array $value): bool
    {
        return !array_is_list($value) || $value === [];
    }

}
