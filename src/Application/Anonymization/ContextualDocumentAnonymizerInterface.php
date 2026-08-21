<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;

interface ContextualDocumentAnonymizerInterface
{
    public function anonymizeDocumentWithContext(string $profile, string $codec, string $payload, AnonymizationContext $context): string;

    public function anonymizeDocumentWithReportAndContext(string $profile, string $codec, string $payload, AnonymizationContext $context): DocumentPayloadAnonymizationResult;
}
