<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration\Provider;

use Faker\Generator;
use PhpAnonymizer\Anonymizer\DataGeneration\DataGeneratorInterface;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Exception\UnsupportedDataTypeException;
use PhpAnonymizer\Anonymizer\Interfaces\FakerAwareInterface;
use function gettype;
use function in_array;
use function is_object;
use function md5;
use function sprintf;

final class DefaultDataGeneratorProvider implements DataGenerationProviderInterface
{
    /** @var DataGeneratorInterface<mixed>[] */
    private array $customGenerators = [];

    private Generator $faker;

    /**
     * @param DataGeneratorInterface<mixed>[] $generators
     */
    public function __construct(
        private readonly array $generators,
        private readonly DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        foreach ($this->generators as $generator) {
            if (!$generator instanceof DataGeneratorInterface) {
                throw new InvalidArgumentException('All generators must implement DataGeneratorInterface');
            }
        }
    }

    /**
     * @param DataGeneratorInterface<mixed> $generator
     */
    public function registerCustomDataGenerator(DataGeneratorInterface $generator): void
    {
        if (!in_array($generator, $this->customGenerators, true)) {
            if (isset($this->faker) && $generator instanceof FakerAwareInterface) {
                $generator->setFaker($this->faker);
            }

            $this->customGenerators[] = $generator;
        }
    }

    /**
     * @param Generator $faker
     */
    public function injectFaker(mixed $faker): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('fakerphp/faker')) {
            throw new MissingPlatformRequirementsException('Faker library is not installed');
        }

        if (!$faker instanceof Generator) {
            throw new InvalidArgumentException('Faker object must be an instance of Faker Generator');
        }

        $this->faker = $faker;

        foreach ($this->generators as $generator) {
            if ($generator instanceof FakerAwareInterface) {
                $generator->setFaker($faker);
            }
        }

        foreach ($this->customGenerators as $generator) {
            if ($generator instanceof FakerAwareInterface) {
                $generator->setFaker($faker);
            }
        }
    }

    public function setSeed(string $seedSecret): void
    {
        $this->faker->seed(md5($seedSecret));
    }

    public function provideDataGenerator(mixed $value, ?string $valueType): DataGeneratorInterface
    {
        foreach ($this->customGenerators as $generator) {
            if ($generator->supports($value, $valueType)) {
                return $generator;
            }
        }

        foreach ($this->generators as $generator) {
            if ($generator->supports($value, $valueType)) {
                return $generator;
            }
        }

        $type = gettype($value);
        if (is_object($value)) {
            $type .= ' (' . $value::class . ')';
        }

        throw new UnsupportedDataTypeException(sprintf('No generator found for value of type: %s (data type: %s)', $valueType, $type));
    }
}
