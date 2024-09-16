<?php

declare(strict_types=1);

require_once __DIR__ . '/person_class.php';

class Order {
    public function __construct(
        public Person $person,
    ) {}
}