<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;
use Throwable;

final class PayloadCodecFailure extends RuntimeException
{
    public static function topLevel(string $profile, string $codec, string $operation, Throwable $previous): self
    {
        return new self(sprintf('Codec "%s" failed to %s payload for profile "%s": %s', $codec, $operation, $profile, $previous->getMessage()), previous: $previous);
    }

    public static function embedded(string $profile, string $codec, string $operation, string $path, Throwable $previous): self
    {
        return new self(sprintf('Codec "%s" failed to %s embedded payload at "%s" for profile "%s": %s', $codec, $operation, $path, $profile, $previous->getMessage()), previous: $previous);
    }

    public static function expectedMap(string $profile, string $codec, string $path = ''): self
    {
        $location = $path === '' ? 'payload' : sprintf('embedded payload at "%s"', $path);

        return new self(sprintf('Codec "%s" returned a non-map %s for profile "%s".', $codec, $location, $profile));
    }
}
