<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Exception;

use RuntimeException;

final class InvalidProfileDefinition extends RuntimeException
{
    public static function forProfile(string $profile, string $reason): self
    {
        return new self(sprintf('Profile "%s" is invalid: %s.', $profile, $reason));
    }

    public static function at(string $profile, string $path): self
    {
        return new self(sprintf('Profile "%s" is invalid at %s.', $profile, $path));
    }
}
