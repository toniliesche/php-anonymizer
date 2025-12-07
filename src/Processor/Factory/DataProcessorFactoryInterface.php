<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;

interface DataProcessorFactoryInterface
{
    public function getDataProcessor(
        ?string $type,
        ?DataAccessProviderInterface $dataAccessProvider = null,
        ?DataGenerationProviderInterface $dataGenerationProvider = null,
        ?DataEncodingProviderInterface $dataEncodingProvider = null,
    ): DataProcessorInterface;
}
