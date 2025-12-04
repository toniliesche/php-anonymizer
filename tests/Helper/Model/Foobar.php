<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class Foobar
{
    public function __construct(
        private readonly string $foo,
        /** @phpstan-ignore-next-line */
        private string $bar,
        private string $baz,
    ) {
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setBar(string $bar): void
    {
        $this->bar = $bar;
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
