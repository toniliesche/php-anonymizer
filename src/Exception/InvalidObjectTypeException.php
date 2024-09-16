<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Exception;

final class InvalidObjectTypeException extends InvalidArgumentException
{
    /**
     * @param string[] $path
     */
    public static function notAnObject(array $path): self
    {
        return new self(\sprintf('Field is not an object : [%s]', \implode('.', $path)));
    }

    /**
     * @param string[] $path
     */
    public static function notAnArray(array $path): self
    {
        return new self(\sprintf('Field is not an array : [%s]', \implode('.', $path)));
    }

    /**
     * @param string[] $path
     */
    public static function notAString(array $path): self
    {
        return new self(\sprintf('Field is not a string : [%s]', \implode('.', $path)));
    }

    /**
     * @param string[] $path
     */
    public static function notAnInteger(array $path): self
    {
        // @codeCoverageIgnoreStart
        return new self(\sprintf('Field is not an integer : [%s]', \implode('.', $path)));
        // @codeCoverageIgnoreEnd
    }
}
