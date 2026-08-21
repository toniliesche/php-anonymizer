<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;

interface DocumentAnonymizerInterface
{
    public function anonymizeDocument(string $profile, string $codec, string $payload): string;

    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload): DocumentPayloadAnonymizationResult;
}
