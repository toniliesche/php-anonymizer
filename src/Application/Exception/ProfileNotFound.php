<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;

final class ProfileNotFound extends RuntimeException
{
    public static function named(string $name): self
    {
        return new self(sprintf('Profile "%s" was not found.', $name));
    }
}
