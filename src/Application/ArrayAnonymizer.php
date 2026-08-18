<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
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
        $registeredCodecs = $this->withBuiltInCodec($codecs);
        $this->codecRegistry = new PayloadCodecRegistry($registeredCodecs);
        /** @var list<ReplacementStrategyInterface> $registeredStrategies */
        $registeredStrategies = $this->withBuiltInStrategies($strategies);
        $strategyRegistry = new ReplacementStrategyRegistry($registeredStrategies);
        $this->profileRegistry->validate($strategyRegistry, $this->codecRegistry);
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

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @return list<ReplacementStrategyInterface>
     */
    private function withBuiltInStrategies(iterable $strategies): array
    {
        $result = [new DefaultRedactionStrategy(), new StarMaskStrategy()];
        foreach ($strategies as $strategy) {
            $result[] = $strategy;
        }

        return $result;
    }

    /**
     * @param iterable<PayloadCodecInterface> $codecs
     * @return list<PayloadCodecInterface>
     */
    private function withBuiltInCodec(iterable $codecs): array
    {
        $result = [new JsonPayloadCodec()];
        foreach ($codecs as $codec) {
            $result[] = $codec;
        }

        return $result;
    }
}
