<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration\Provider;

use Faker\Generator;
use PhpAnonymizer\Anonymizer\DataGeneration\DataGeneratorInterface;

interface DataGenerationProviderInterface
{
    /**
     * @template T
     *
     * @param T $value
     *
     * @return DataGeneratorInterface<T>
     */
    public function provideDataGenerator(mixed $value, ?string $valueType): DataGeneratorInterface;

    /**
     * @param Generator $faker
     */
    public function injectFaker(mixed $faker): void;

    public function setSeed(string $seedSecret): void;
}
