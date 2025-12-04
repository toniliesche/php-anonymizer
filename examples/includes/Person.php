<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Examples;

class Person
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }
}
