<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class PrivateFoobar
{
    public function __construct(
        private readonly string $foo,
        private readonly string $bar,
        private string $baz,
    ) {
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function getBar(): string
    {
        return $this->bar;
    }

    public function getBaz(): string
    {
        return $this->baz;
    }

    public function setBaz(string $baz): void
    {
        $this->baz = $baz;
    }
}
