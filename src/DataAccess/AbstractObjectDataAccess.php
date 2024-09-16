<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use function is_object;

abstract class AbstractObjectDataAccess implements DataAccessInterface
{
    public function supports(mixed $parent): bool
    {
        return is_object($parent);
    }
}
