<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Examples;

class Order
{
    public function __construct(
        public Person $person,
    ) {
    }
}
