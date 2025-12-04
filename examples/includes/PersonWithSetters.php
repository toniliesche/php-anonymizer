<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Examples;

class PersonWithSetters
{
    public function __construct(
        private string $firstName,
        private string $lastName,
    ) {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }
}
