<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;

final class ContextRequired extends RuntimeException
{
    public static function forStrategy(string $profile, string $path, string $strategy): self
    {
        return new self(sprintf('Profile "%s" selected path "%s" with contextual strategy "%s"; use the contextual anonymizer API.', $profile, $path, $strategy));
    }
}
