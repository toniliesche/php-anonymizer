<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;

final class UnsupportedValue extends RuntimeException
{
    public static function at(string $profile, string $path, mixed $value): self
    {
        return new self(sprintf('Profile "%s" selected path "%s", but its value has unsupported type %s.', $profile, $path, get_debug_type($value)));
    }
}
