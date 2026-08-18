<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;

interface AnonymizerInterface
{
    /**
     * @param array<string, mixed>|object $data
     * @return array<string, mixed>|object
     */
    public function anonymize(string $profile, array|object $data): array|object;

    /** @param array<string, mixed>|object $data */
    public function anonymizeWithReport(string $profile, array|object $data): object;

    public function anonymizePayload(string $profile, string $codec, string $payload): string;

    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload): PayloadAnonymizationResult;
}
