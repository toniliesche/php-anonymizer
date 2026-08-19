<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use RuntimeException;

final class ExecutionLimitExceeded extends RuntimeException
{
    public static function forPath(string $profile, string $limit, int $configured, JsonPointer $path): self
    {
        return self::forLocation($profile, $limit, $configured, (string) $path);
    }

    public static function forLocation(string $profile, string $limit, int $configured, string $path): self
    {
        return new self(sprintf('Anonymization limit "%s" (%d) exceeded for profile "%s" at "%s".', $limit, $configured, $profile, $path));
    }
}
