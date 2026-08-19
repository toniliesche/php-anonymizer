<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use Throwable;

final readonly class ArrayAnonymizer implements AnonymizerInterface, BatchAnonymizerInterface, AuditAnonymizerInterface, ContextualAnonymizerInterface
{
    private ProfileRegistry $profileRegistry;
    private PayloadCodecRegistry $codecRegistry;
    private ArrayTraversal $traversal;
    private AnonymizationLimits $limits;

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public function __construct(ProfileProviderInterface $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null)
    {
        $this->limits = $limits ?? new AnonymizationLimits();
        $this->profileRegistry = new ProfileRegistry($profiles->profiles());
        /** @var list<PayloadCodecInterface> $registeredCodecs */
        $registeredCodecs = [new JsonPayloadCodec(), ...iterator_to_array($codecs, false)];
        $this->codecRegistry = new PayloadCodecRegistry($registeredCodecs);
        /** @var list<ReplacementStrategyInterface> $registeredStrategies */
        $registeredStrategies = [new DefaultRedactionStrategy(), new StarMaskStrategy(), ...iterator_to_array($strategies, false)];
        $strategyRegistry = new ReplacementStrategyRegistry($registeredStrategies);
        new ProfileReferenceValidator()->validate($this->profileRegistry, $strategyRegistry, $this->codecRegistry);
        $this->traversal = new ArrayTraversal($this->profileRegistry, $strategyRegistry, $this->codecRegistry, $this->limits);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array|object $data): array
    {
        if (is_object($data)) {
            throw new InvalidArgumentException('ArrayAnonymizer accepts arrays only; configure the Symfony Serializer integration to anonymize objects.');
        }

        return $this->traversal->apply($profile, $data, false)->data();
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    #[Override]
    public function anonymizeWithContext(string $profile, array $data, AnonymizationContext $context): array
    {
        return $this->traversal->apply($profile, $data, false, context: $context)->data();
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithReportAndContext(string $profile, array $data, AnonymizationContext $context): AnonymizationResult
    {
        return $this->traversal->apply($profile, $data, context: $context);
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithReport(string $profile, array|object $data): AnonymizationResult
    {
        if (is_object($data)) {
            throw new InvalidArgumentException('ArrayAnonymizer accepts arrays only; configure the Symfony Serializer integration to anonymize objects.');
        }
        return $this->traversal->apply($profile, $data);
    }

    #[Override]
    public function anonymizePayload(string $profile, string $codec, string $payload): string
    {
        return $this->anonymizePayloadWithReport($profile, $codec, $payload)->payload();
    }

    /**
     * @param iterable<mixed> $records
     * @return iterable<array<string, mixed>>
     */
    #[Override]
    public function anonymizeRecords(string $profile, iterable $records): iterable
    {
        foreach ($records as $index => $record) {
            if (!is_array($record) || array_is_list($record)) {
                $ordinal = is_int($index) || is_string($index) ? $index : get_debug_type($index);
                throw new InvalidArgumentException(sprintf('Batch record %s must be an associative array.', $ordinal));
            }

            /** @var array<string, mixed> $map */
            $map = [];
            foreach ($record as $key => $value) {
                $map[(string) $key] = $value;
            }
            yield $this->anonymize($profile, $map);
        }
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithAudit(string $profile, array $data): AuditAnonymizationResult
    {
        return $this->buildAuditResult($profile, $data, null);
    }

    /** @param array<string, mixed> $data */
    private function buildAuditResult(string $profile, array $data, ?AnonymizationContext $context): AuditAnonymizationResult
    {
        $definition = $this->profileRegistry->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }
        $result = $this->traversal->apply($profile, $data, context: $context);
        $counts = [];
        foreach ($result->events() as $event) {
            $key = implode("\0", [$event['path'], $event['action'], $event['handler'] ?? '']);
            $counts[$key] ??= ['path' => $event['path'], 'action' => $event['action'], 'handler' => $event['handler'], 'count' => 0];
            ++$counts[$key]['count'];
        }
        ksort($counts);
        $entries = [];
        foreach ($counts as $entry) {
            $entries[] = new AuditEntry($profile, $definition->fingerprint(), $entry['path'], $entry['action'], $entry['handler'], $entry['count']);
        }
        return new AuditAnonymizationResult($result->data(), $entries);
    }

    #[Override]
    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload): PayloadAnonymizationResult
    {
        return $this->anonymizePayloadWithReportAndContext($profile, $codec, $payload, null);
    }

    #[Override]
    public function anonymizePayloadWithContext(string $profile, string $codec, string $payload, AnonymizationContext $context): string
    {
        return $this->anonymizePayloadWithReportAndContext($profile, $codec, $payload, $context)->payload();
    }

    #[Override]
    public function anonymizePayloadWithReportAndContext(string $profile, string $codec, string $payload, ?AnonymizationContext $context): PayloadAnonymizationResult
    {
        $payloadCodec = $this->codecRegistry->get($codec);
        if (!$payloadCodec instanceof PayloadCodecInterface) {
            throw new InvalidArgumentException(sprintf('Payload codec "%s" is not registered.', $codec));
        }

        try {
            new ExecutionBudget($this->limits)->inputBytes($profile, '', mb_strlen($payload, 'UTF-8'));
            $data = $payloadCodec->decode($payload);
        } catch (ExecutionLimitExceeded $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'decode', $exception);
        }
        if (!$this->isMap($data)) {
            throw PayloadCodecFailure::expectedMap($profile, $codec);
        }
        /** @var array<string, mixed> $data */

        $result = !$context instanceof AnonymizationContext ? $this->anonymizeWithReport($profile, $data) : $this->anonymizeWithReportAndContext($profile, $data, $context);
        try {
            $encoded = $payloadCodec->encode($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'encode', $exception);
        }

        return new PayloadAnonymizationResult($encoded, $result);
    }

    /** @param iterable<mixed> $records
     *  @return iterable<array<string, mixed>>
     */
    #[Override]
    public function anonymizeRecordsWithContext(string $profile, iterable $records, AnonymizationContext $context): iterable
    {
        foreach ($records as $index => $record) {
            if (!is_array($record) || array_is_list($record)) {
                $ordinal = is_int($index) || is_string($index) ? $index : get_debug_type($index);
                throw new InvalidArgumentException(sprintf('Batch record %s must be an associative array.', $ordinal));
            }
            /** @var array<string, mixed> $map */
            $map = [];
            foreach ($record as $key => $value) {
                $map[(string) $key] = $value;
            }
            yield $this->anonymizeWithContext($profile, $map, $context);
        }
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithAuditAndContext(string $profile, array $data, AnonymizationContext $context): AuditAnonymizationResult
    {
        return $this->buildAuditResult($profile, $data, $context);
    }

    /** @param array<mixed, mixed> $value */
    private function isMap(array $value): bool
    {
        return !array_is_list($value) || $value === [];
    }

}
