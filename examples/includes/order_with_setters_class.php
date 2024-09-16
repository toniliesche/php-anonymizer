<?php

declare(strict_types=1);

require_once __DIR__ . '/person_class.php';

class OrderWithSetters {
    public function __construct(
        private Person $person,
    ) {}

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
    }
}