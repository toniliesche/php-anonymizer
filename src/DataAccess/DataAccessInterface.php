<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;

interface DataAccessInterface
{
    /**
     * @param string[] $path
     *
     * @throws InvalidObjectTypeException
     */
    public function hasChild(array $path, mixed $parent, string $name): bool;

    /**
     * @param string[] $path
     *
     * @throws InvalidObjectTypeException
     * @throws FieldDoesNotExistException
     */
    public function getChild(array $path, mixed $parent, string $name): mixed;

    /**
     * @param string[] $path
     *
     * @throws InvalidObjectTypeException
     * @throws FieldDoesNotExistException
     */
    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void;

    public function supports(mixed $parent): bool;
}
