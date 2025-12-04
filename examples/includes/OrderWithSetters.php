<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Examples;

class OrderWithSetters
{
    public function __construct(
        private Person $person,
    ) {
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
    }
}
