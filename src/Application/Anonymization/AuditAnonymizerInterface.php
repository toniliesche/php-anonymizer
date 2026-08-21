<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;

interface AuditAnonymizerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function anonymizeWithAudit(string $profile, array $data): AuditAnonymizationResult;
}
