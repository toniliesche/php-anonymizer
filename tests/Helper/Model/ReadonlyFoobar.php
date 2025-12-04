<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final readonly class ReadonlyFoobar
{
    public function __construct(
        public string $foo,
        public string $bar,
        public string $baz,
    ) {
    }
}
