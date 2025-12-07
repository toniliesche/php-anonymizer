<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess\Provider;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;

interface DataAccessProviderInterface
{
    public function supports(string $dataAccess): bool;

    public function provideDataAccess(string $dataAccess): DataAccessInterface;
}
