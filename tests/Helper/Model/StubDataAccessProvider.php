<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;

final readonly class StubDataAccessProvider implements DataAccessProviderInterface
{
    public function __construct(
        private DataAccessInterface $dataAccess,
    ) {
    }

    public function supports(string $dataAccess): bool
    {
        return true;
    }

    public function provideDataAccess(string $dataAccess): DataAccessInterface
    {
        return $this->dataAccess;
    }
}
