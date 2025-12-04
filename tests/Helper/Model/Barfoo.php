<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class Barfoo
{
    private string $foo;

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}
