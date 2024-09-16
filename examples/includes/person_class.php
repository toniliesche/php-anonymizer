<?php

declare(strict_types=1);

class Person
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {

    }
}
