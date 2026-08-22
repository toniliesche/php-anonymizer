<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResultInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;

interface AnonymizerInterface
{
    /**
     * @param array<string, mixed>|object $data
     *
     * @return array<string, mixed>|object
     */
    public function anonymize(string $profile, array|object $data, ?AnonymizationContext $context = null): array|object;

    /**
     * @param array<string, mixed>|object $data
     */
    public function anonymizeWithReport(string $profile, array|object $data, ?AnonymizationContext $context = null): AnonymizationResultInterface;

    public function anonymizePayload(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string;

    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): PayloadAnonymizationResult;

    /**
     * @param iterable<array<string, mixed>> $records
     *
     * @return iterable<array<string, mixed>>
     */
    public function anonymizeRecords(string $profile, iterable $records, ?AnonymizationContext $context = null): iterable;

    /**
     * @param array<string, mixed> $data
     */
    public function anonymizeWithAudit(string $profile, array $data, ?AnonymizationContext $context = null): AuditAnonymizationResult;

    public function anonymizeDocument(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string;

    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): DocumentPayloadAnonymizationResult;

    /**
     * @param iterable<mixed> $lines
     */
    public function anonymizeNdjson(string $profile, iterable $lines, ?AnonymizationContext $context = null): NdjsonAnonymizationResult;

    public function anonymizeObject(ObjectAnonymizationRequest $request): object;

    public function anonymizeObjectWithReport(ObjectAnonymizationRequest $request): AnonymizationResultInterface;
}
