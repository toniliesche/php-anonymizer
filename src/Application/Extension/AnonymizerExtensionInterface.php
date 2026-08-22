<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;

interface AnonymizerExtensionInterface
{
    public function configure(AnonymizerBuilder $builder): AnonymizerBuilder;
}
