<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;

final class UnsupportedValue extends RuntimeException
{
    public static function at(string $profile, string $field, mixed $value): self
    {
        return new self(sprintf('Profile "%s" selected field "%s", but its value has unsupported type %s.', $profile, $field, get_debug_type($value)));
    }
}
