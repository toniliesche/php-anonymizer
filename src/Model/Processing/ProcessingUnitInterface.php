<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Processing;

interface ProcessingUnitInterface
{
    public function process(?string $encoding = null): mixed;
}
