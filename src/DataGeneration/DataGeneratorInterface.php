<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration;

/**
 * @template T
 */
interface DataGeneratorInterface
{
    public function supports(mixed $value, ?string $valueType): bool;

    /**
     * @param string[] $path
     * @param T $oldValue
     *
     * @return T
     */
    public function generate(array $path, mixed $oldValue, ?string $valueType): mixed;
}
