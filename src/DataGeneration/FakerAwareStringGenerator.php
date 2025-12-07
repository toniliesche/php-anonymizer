<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration;

use Faker\Generator;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataField;
use PhpAnonymizer\Anonymizer\Exception\CannotResolveValueException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @template-implements FakerAwareDataGeneratorInterface<string>
 */
final class FakerAwareStringGenerator implements FakerAwareDataGeneratorInterface
{
    private const DATA_FIELDS = [
        DataField::BUILDING_NUMBER->value,
        DataField::CITY->value,
        DataField::COMPANY->value,
        DataField::COUNTRY->value,
        DataField::EMAIL->value,
        DataField::FIRST_NAME->value,
        DataField::HOUSE_NUMBER->value,
        DataField::LAST_NAME->value,
        DataField::NAME->value,
        DataField::PASSWORD->value,
        DataField::POSTCODE->value,
        DataField::STREET->value,
        DataField::STREET_NUMBER->value,
        DataField::USERNAME->value,
        DataField::ZIP->value,
    ];

    private Generator $faker;

    /**
     * @param null|DataGeneratorInterface<string> $fallbackDataGenerator
     */
    public function __construct(
        private readonly ?DataGeneratorInterface $fallbackDataGenerator = null,
        private readonly DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
    }

    /**
     * @param Generator $faker
     */
    public function setFaker(mixed $faker): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('fakerphp/faker')) {
            throw new MissingPlatformRequirementsException('Faker library is not installed');
        }

        if (!$faker instanceof Generator) {
            throw new InvalidArgumentException('Faker object must be an instance of Faker Generator');
        }

        $this->faker = $faker;
    }

    public function supports(mixed $value, ?string $valueType): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (isset($this->faker) && in_array($valueType, self::DATA_FIELDS, true)) {
            return true;
        }

        return $this->fallbackDataGenerator?->supports($value, $valueType) ?? false;
    }

    public function generate(array $path, mixed $oldValue, ?string $valueType): mixed
    {
        return $this->resolve($valueType) ?? $this->fallbackDataGenerator?->generate($path, $oldValue, $valueType) ?? throw new CannotResolveValueException(sprintf('Cannot resolve value for path %s', implode('.', $path)));
    }

    private function resolve(?string $valueType): ?string
    {
        if (!isset($this->faker)) {
            return null;
        }

        return match ($valueType) {
            DataField::EMAIL->value => $this->faker->safeEmail,
            DataField::FIRST_NAME->value => $this->faker->firstName,
            DataField::LAST_NAME->value => $this->faker->lastName,
            DataField::NAME->value => $this->faker->name,
            DataField::STREET->value => $this->faker->streetName,
            DataField::BUILDING_NUMBER->value, DataField::HOUSE_NUMBER->value, DataField::STREET_NUMBER->value => $this->faker->buildingNumber,
            DataField::CITY->value => $this->faker->city,
            DataField::POSTCODE->value, DataField::ZIP->value => $this->faker->postcode,
            DataField::COUNTRY->value => $this->faker->country,
            DataField::COMPANY->value => $this->faker->company,
            DataField::USERNAME->value => $this->faker->userName,
            DataField::PASSWORD->value => $this->faker->password,
            default => null,
        };
    }
}
