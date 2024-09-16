<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Interfaces;

use Faker\Generator;

interface FakerAwareInterface
{
    /**
     * @param Generator $faker
     */
    public function setFaker(mixed $faker): void;
}
