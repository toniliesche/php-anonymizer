<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class PrivateFooParent
{
    /**
     * @param array<int, mixed> $array
     */
    public function __construct(
        private PrivateFoobar $foobar,
        private array $array,
    ) {
    }

    public function getFoobar(): PrivateFoobar
    {
        return $this->foobar;
    }

    public function setFoobar(PrivateFoobar $foobar): void
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
