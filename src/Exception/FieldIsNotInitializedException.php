<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Exception;

final class FieldIsNotInitializedException extends NotFoundException
{
    /**
     * @param string[] $path
     */
    public static function fromPath(array $path): self
    {
        return new self(sprintf('Field is not initialized : [%s]', implode('.', $path)));
    }
}
