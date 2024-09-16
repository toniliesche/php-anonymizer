<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Exception;

use function implode;
use function sprintf;

final class FieldDoesNotExistException extends NotFoundException
{
    /**
     * @param string[] $path
     */
    public static function fromPath(array $path): self
    {
        return new self(sprintf('Field does not exist : [%s]', implode('.', $path)));
    }

    /**
     * @param string[] $path
     */
    public static function orIsNotAccessibleFromPath(array $path): self
    {
        return new self(sprintf('Field does not exist or is not accessible : [%s]', implode('.', $path)));
    }
}
