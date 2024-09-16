<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;

interface DataAccessProviderFactoryInterface
{
    public function getDataAccessProvider(?string $type): ?DataAccessProviderInterface;
}
