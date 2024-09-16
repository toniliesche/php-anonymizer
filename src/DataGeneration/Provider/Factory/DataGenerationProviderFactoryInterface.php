<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory;

use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;

interface DataGenerationProviderFactoryInterface
{
    public function getDataGenerationProvider(?string $type): ?DataGenerationProviderInterface;
}
