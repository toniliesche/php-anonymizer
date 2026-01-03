<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class FooParent
{
    /**
     * @param array<int, mixed> $array
     */
    public function __construct(
        private Foobar $foobar,
        private array $array,
    ) {
    }

    public function getFoobar(): Foobar
    {
        return $this->foobar;
    }

    public function setFoobar(Foobar $foobar): void
    {
        $this->foobar = $foobar;
    }

    /**
     * @return array<int, mixed>
     */
    public function getArray(): array
    {
        return $this->array;
    }

    /**
     * @param array<int, mixed> $array
     */
    public function setArray(array $array): void
    {
        $this->array = $array;
    }
}
