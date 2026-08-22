<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResultInterface;

/**
 * Adapter port for immutable object anonymization.
 */
interface ObjectAnonymizerInterface
{
    public function anonymize(ObjectAnonymizationRequest $request, AnonymizerInterface $anonymizer): AnonymizationResultInterface;
}
