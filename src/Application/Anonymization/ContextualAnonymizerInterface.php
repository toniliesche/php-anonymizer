<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;

interface ContextualAnonymizerInterface
{
    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    public function anonymizeWithContext(string $profile, array $data, AnonymizationContext $context): array;
    /** @param array<string, mixed> $data */
    public function anonymizeWithReportAndContext(string $profile, array $data, AnonymizationContext $context): AnonymizationResult;
    public function anonymizePayloadWithContext(string $profile, string $codec, string $payload, AnonymizationContext $context): string;
    public function anonymizePayloadWithReportAndContext(string $profile, string $codec, string $payload, AnonymizationContext $context): PayloadAnonymizationResult;
    /** @param iterable<mixed> $records
     *  @return iterable<array<string, mixed>>
     */
    public function anonymizeRecordsWithContext(string $profile, iterable $records, AnonymizationContext $context): iterable;
    /** @param array<string, mixed> $data */
    public function anonymizeWithAuditAndContext(string $profile, array $data, AnonymizationContext $context): AuditAnonymizationResult;
}
