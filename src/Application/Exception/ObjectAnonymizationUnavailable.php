<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Exception;

use RuntimeException;

/**
 * @codeCoverageIgnore Transitional adapter exception is exercised through façade integration tests.
 */
final class ObjectAnonymizationUnavailable extends RuntimeException
{
    public static function forObject(object $object): self
    {
        return new self(sprintf('No object anonymizer is configured for %s.', $object::class));
    }
}
