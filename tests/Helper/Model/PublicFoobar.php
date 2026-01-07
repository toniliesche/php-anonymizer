<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class PublicFoobar
{
    public function __construct(
        private readonly string $foo,
        private string $bar,
        public string $baz,
    ) {
    }

    public function getBar(): string
    {
        return $this->bar;
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

    /**
     * @return array<string, string>
     */
    public function export(): array
    {
        return [
            'foo' => $this->foo,
            'bar' => $this->bar,
            'baz' => $this->baz,
        ];
    }
}
