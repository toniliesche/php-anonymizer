<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

interface AnonymizerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function anonymize(string $profile, array $data): array;

    /** @param array<string, mixed> $data */
    public function anonymizeWithReport(string $profile, array $data): AnonymizationResult;
}
