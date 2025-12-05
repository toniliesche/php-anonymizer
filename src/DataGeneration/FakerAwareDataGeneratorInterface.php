<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration;

use Faker\Generator;

/**
 * @template T
 *
 * @template-extends DataGeneratorInterface<T>
 */
interface FakerAwareDataGeneratorInterface extends DataGeneratorInterface
{
    /**
     * @param Generator $faker
     */
    public function setFaker(mixed $faker): void;
}
