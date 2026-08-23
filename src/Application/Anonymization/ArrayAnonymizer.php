<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResultInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/**
 * @internal use AnonymizerBuilder to obtain the unified façade
 */
final readonly class ArrayAnonymizer implements AnonymizerInterface
{
    private ProfileRegistry $profileRegistry;

    private ArrayTraversal $traversal;

    private NdjsonAnonymizationService $ndjson;

    private PayloadAnonymizationService $payloads;

    /**
     * @param iterable<ReplacementStrategyInterface> $strategies
     * @param iterable<PayloadCodecInterface> $codecs
     * @param iterable<ValueTransformationInterface> $transformations
     */
    public function __construct(ProfileProviderInterface|AnonymizerRuntime $profiles, iterable $strategies = [], iterable $codecs = [], ?AnonymizationLimits $limits = null, iterable $transformations = [])
    {
        $runtime = $profiles instanceof AnonymizerRuntime
            ? $profiles
            : new RuntimeAssembler()->assemble($profiles, $strategies, $codecs, $limits, $transformations);
        $this->profileRegistry = $runtime->profiles;
        $this->traversal = $runtime->traversal;
        $this->ndjson = $runtime->ndjson;
        $this->payloads = $runtime->payloads;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array|object $data, ?AnonymizationContext $context = null): array
    {
        if (is_object($data)) {
            throw new InvalidArgumentException('ArrayAnonymizer accepts arrays only; configure the Symfony Serializer integration to anonymize objects.');
        }

        // @infection-ignore-next-line Report collection does not affect anonymized data output.
        return $this->traversal->apply($profile, $data, false, context: $context)->data();
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function anonymizeWithReport(string $profile, array|object $data, ?AnonymizationContext $context = null): AnonymizationResult
    {
        if (is_object($data)) {
            throw new InvalidArgumentException('ArrayAnonymizer accepts arrays only; configure the Symfony Serializer integration to anonymize objects.');
        }

        return $this->traversal->apply($profile, $data, context: $context);
    }

    #[Override]
    public function anonymizePayload(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->payloads->anonymizePayload($profile, $codec, $payload, $context);
    }

    /**
     * @param iterable<mixed> $records
     *
     * @return iterable<array<string, mixed>>
     */
    #[Override]
    public function anonymizeRecords(string $profile, iterable $records, ?AnonymizationContext $context = null): iterable
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

            yield $this->anonymize($profile, $map, $context);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function anonymizeWithAudit(string $profile, array $data, ?AnonymizationContext $context = null): AuditAnonymizationResult
    {
        return $this->buildAuditResult($profile, $data, $context);
    }

    #[Override]
    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): PayloadAnonymizationResult
    {
        return $this->payloads->anonymizePayloadWithReport($profile, $codec, $payload, $context);
    }

    #[Override]
    public function anonymizeDocument(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->payloads->anonymizeDocument($profile, $codec, $payload, $context);
    }

    #[Override]
    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): DocumentPayloadAnonymizationResult
    {
        return $this->payloads->anonymizeDocumentWithReport($profile, $codec, $payload, $context);
    }

    /**
     * @param iterable<mixed> $lines
     */
    #[Override]
    public function anonymizeNdjson(string $profile, iterable $lines, ?AnonymizationContext $context = null): NdjsonAnonymizationResult
    {
        return $this->ndjson->anonymize($profile, $lines, $context);
    }

    /**
     * @codeCoverageIgnore Transitional array adapter intentionally rejects object roots.
     */
    #[Override]
    public function anonymizeObject(ObjectAnonymizationRequest $request): object
    {
        throw ObjectAnonymizationUnavailable::forObject($request->object());
    }

    /**
     * @codeCoverageIgnore Transitional array adapter intentionally rejects object roots.
     */
    #[Override]
    public function anonymizeObjectWithReport(ObjectAnonymizationRequest $request): AnonymizationResultInterface
    {
        throw ObjectAnonymizationUnavailable::forObject($request->object());
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
}
