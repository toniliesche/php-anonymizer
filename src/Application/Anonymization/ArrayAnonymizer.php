<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use LogicException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\DocumentPayloadCodecInterface;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;
use Throwable;

// @phpstan-ignore complexity.classLike (the facade intentionally owns the additive anonymization ports)
final readonly class ArrayAnonymizer implements AnonymizerInterface, AuditAnonymizerInterface, BatchAnonymizerInterface, ContextualAnonymizerInterface, ContextualDocumentAnonymizerInterface, ContextualNdjsonAnonymizerInterface, DocumentAnonymizerInterface, NdjsonAnonymizerInterface
{
    private ProfileRegistry $profileRegistry;

    private PayloadCodecRegistry $codecRegistry;

    private ArrayTraversal $traversal;

    private AnonymizationLimits $limits;

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public function __construct(ProfileProviderInterface $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null, iterable $transformations = [])
    {
        $this->limits = $limits ?? new AnonymizationLimits();
        $this->profileRegistry = new ProfileRegistry($profiles->profiles());

        /** @var list<PayloadCodecInterface> $registeredCodecs */
        // @infection-ignore-next-line Registries consume codec values and never expose iterable keys.
        $registeredCodecs = [new JsonPayloadCodec(), ...iterator_to_array($codecs, false)];
        $this->codecRegistry = new PayloadCodecRegistry($registeredCodecs);

        /** @var list<ReplacementStrategyInterface> $registeredStrategies */
        // @infection-ignore-next-line Registries consume strategy values and never expose iterable keys.
        $registeredStrategies = [new DefaultRedactionStrategy(), new StarMaskStrategy(), ...iterator_to_array($strategies, false)];
        $strategyRegistry = new ReplacementStrategyRegistry($registeredStrategies);
        $transformationRegistry = new TransformationRegistry([...CoreTransformationFactory::defaults(), ...iterator_to_array($transformations, false)]);
        new ProfileReferenceValidator()->validate($this->profileRegistry, $strategyRegistry, $this->codecRegistry, $transformationRegistry);
        $this->traversal = new ArrayTraversal($this->profileRegistry, $strategyRegistry, $this->codecRegistry, $transformationRegistry, $this->limits);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array|object $data): array
    {
        if (is_object($data)) {
            throw new InvalidArgumentException('ArrayAnonymizer accepts arrays only; configure the Symfony Serializer integration to anonymize objects.');
        }

        // @infection-ignore-next-line Report collection does not affect anonymized data output.
        return $this->traversal->apply($profile, $data, false)->data();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymizeWithContext(string $profile, array $data, AnonymizationContext $context): array
    {
        // @infection-ignore-next-line Report collection does not affect anonymized data output.
        return $this->traversal->apply($profile, $data, false, context: $context)->data();
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function anonymizeWithReportAndContext(string $profile, array $data, AnonymizationContext $context): AnonymizationResult
    {
        return $this->traversal->apply($profile, $data, context: $context);
    }

    /**
     * @param array<string, mixed> $data
     */
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
     *
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
                // @infection-ignore-next-line PHP canonicalizes numeric string array keys to the same integer key.
                $map[(string) $key] = $value;
            }

            yield $this->anonymize($profile, $map);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function anonymizeWithAudit(string $profile, array $data): AuditAnonymizationResult
    {
        return $this->buildAuditResult($profile, $data, null);
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

    #[Override]
    public function anonymizeDocument(string $profile, string $codec, string $payload): string
    {
        return $this->anonymizeDocumentWithReportAndContextInternal($profile, $codec, $payload, null)->payload();
    }

    #[Override]
    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload): DocumentPayloadAnonymizationResult
    {
        return $this->anonymizeDocumentWithReportAndContextInternal($profile, $codec, $payload, null);
    }

    #[Override]
    public function anonymizeDocumentWithContext(string $profile, string $codec, string $payload, AnonymizationContext $context): string
    {
        return $this->anonymizeDocumentWithReportAndContext($profile, $codec, $payload, $context)->payload();
    }

    #[Override]
    public function anonymizeDocumentWithReportAndContext(string $profile, string $codec, string $payload, AnonymizationContext $context): DocumentPayloadAnonymizationResult
    {
        return $this->anonymizeDocumentWithReportAndContextInternal($profile, $codec, $payload, $context);
    }

    /**
     * @param iterable<mixed> $lines
     */
    #[Override]
    public function anonymizeNdjson(string $profile, iterable $lines): NdjsonAnonymizationResult
    {
        return $this->anonymizeNdjsonInternal($profile, $lines, null);
    }

    /**
     * @param iterable<mixed> $lines
     */
    #[Override]
    public function anonymizeNdjsonWithContext(string $profile, iterable $lines, AnonymizationContext $context): NdjsonAnonymizationResult
    {
        return $this->anonymizeNdjsonInternal($profile, $lines, $context);
    }

    /** @param iterable<mixed> $records
     * @return iterable<array<string, mixed>>
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
                // @infection-ignore-next-line PHP canonicalizes numeric string array keys to the same integer key.
                $map[(string) $key] = $value;
            }

            yield $this->anonymizeWithContext($profile, $map, $context);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function anonymizeWithAuditAndContext(string $profile, array $data, AnonymizationContext $context): AuditAnonymizationResult
    {
        return $this->buildAuditResult($profile, $data, $context);
    }

    private function anonymizeDocumentWithReportAndContextInternal(string $profile, string $codec, string $payload, ?AnonymizationContext $context): DocumentPayloadAnonymizationResult
    {
        $payloadCodec = $this->codecRegistry->get($codec);
        if (!$payloadCodec instanceof DocumentPayloadCodecInterface) {
            throw new InvalidArgumentException(sprintf('Payload codec "%s" does not support document roots.', $codec));
        }

        try {
            new ExecutionBudget($this->limits)->inputBytes($profile, '', mb_strlen($payload, 'UTF-8'));
            $data = $payloadCodec->decodeDocument($payload);
        } catch (ExecutionLimitExceeded $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'decode', $exception);
        }

        $definition = $this->profileRegistry->get($profile);
        if ($definition instanceof ProfileDefinition && $codec === 'json') {
            $first = ltrim($payload)[0] ?? '';
            if (($first === '[' && $definition->root() === ProfileRoot::MAP) || ($first === '{' && $definition->root() === ProfileRoot::LIST)) {
                throw new InvalidArgumentException(sprintf('Profile "%s" root does not match the document root.', $profile));
            }
        }

        $result = $this->traversal->applyDocument($profile, $data, $context);

        try {
            $encoded = $payloadCodec->encodeDocument($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'encode', $exception);
        }

        return new DocumentPayloadAnonymizationResult($encoded, $result);
    }

    /**
     * @param iterable<mixed> $lines
     */
    // @phpstan-ignore complexity.functionLike (stream validation and finalization are explicit)
    private function anonymizeNdjsonInternal(string $profile, iterable $lines, ?AnonymizationContext $context): NdjsonAnonymizationResult
    {
        $definition = $this->profileRegistry->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }
        if ($definition->root() !== ProfileRoot::LIST) {
            throw new InvalidArgumentException(sprintf('Profile "%s" requires root=list for NDJSON.', $profile));
        }

        /** @var array<string, array{path: string, action: string, handler: ?string, count: int}> $counts */
        $counts = [];
        $complete = false;
        $audit = null;
        $json = new JsonPayloadCodec();
        $output = (function () use ($profile, $definition, $lines, $context, &$counts, &$complete, &$audit, $json): iterable {
            foreach ($lines as $ordinal => $line) {
                if (!is_string($line)) {
                    throw new InvalidArgumentException(sprintf('NDJSON record %s must be a string.', $this->formatOrdinal($ordinal)));
                }
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    throw new InvalidArgumentException(sprintf('NDJSON record %s must not be blank.', $this->formatOrdinal($ordinal)));
                }

                try {
                    $decoded = $json->decodeDocument($line);
                } catch (Throwable $exception) {
                    throw PayloadCodecFailure::topLevel($profile, 'json', 'decode', $exception);
                }
                if ((ltrim($line)[0] ?? '') !== '{') {
                    throw new InvalidArgumentException(sprintf('NDJSON record %s must be a JSON object.', $this->formatOrdinal($ordinal)));
                }

                /** @var array<string, mixed> $map */
                $map = $decoded;
                $result = $context instanceof AnonymizationContext
                    ? $this->traversal->apply($profile, $map, true, context: $context)
                    : $this->traversal->apply($profile, $map);
                foreach ($result->events() as $event) {
                    $path = '/*' . $event['path'];
                    $key = implode("\0", [$path, $event['action'], $event['handler'] ?? '']);
                    $counts[$key] ??= ['path' => $path, 'action' => $event['action'], 'handler' => $event['handler'], 'count' => 0];
                    ++$counts[$key]['count'];
                }

                yield $json->encodeDocument($result->data());
            }
            ksort($counts);
            $entries = [];
            foreach ($counts as $entry) {
                $entries[] = new AuditEntry($profile, $definition->fingerprint(), $entry['path'], $entry['action'], $entry['handler'], $entry['count']);
            }
            $audit = new NdjsonAuditResult($entries);
            $complete = true;
        })();

        return new NdjsonAnonymizationResult($output, static function () use (&$complete, &$audit): NdjsonAuditResult {
            if (!$complete || !$audit instanceof NdjsonAuditResult) {
                throw new LogicException('NDJSON audit is available only after successful stream exhaustion.');
            }

            return $audit;
        });
    }

    private function formatOrdinal(mixed $ordinal): string
    {
        return is_int($ordinal) || is_string($ordinal) ? (string) $ordinal : get_debug_type($ordinal);
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<mixed, mixed> $value
     */
    private function isMap(array $value): bool
    {
        return !array_is_list($value) || $value === [];
    }
}
