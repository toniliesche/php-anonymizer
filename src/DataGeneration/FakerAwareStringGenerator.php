<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataField;
use PhpAnonymizer\Anonymizer\Exception\CannotResolveValueException;
use PhpAnonymizer\Anonymizer\Interfaces\FakerAwareInterface;
use PhpAnonymizer\Anonymizer\Traits\FakerAwareTrait;

/**
 * @template-implements DataGeneratorInterface<string>
 */
class FakerAwareStringGenerator implements DataGeneratorInterface, FakerAwareInterface
{
    use FakerAwareTrait;

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

    /**
     * @param null|DataGeneratorInterface<string> $fallbackDataGenerator
     */
    public function __construct(
        private readonly ?DataGeneratorInterface $fallbackDataGenerator = null,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        $this->dependencyChecker = $dependencyChecker;
    }

    public function supports(mixed $value, ?string $valueType): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        if (isset($this->faker) && \in_array($valueType, self::DATA_FIELDS, true)) {
            return true;
        }

        return $this->fallbackDataGenerator?->supports($value, $valueType) ?? false;
    }

    public function generate(array $path, mixed $oldValue, ?string $valueType): mixed
    {
        return $this->resolve($valueType) ?? $this->fallbackDataGenerator?->generate($path, $oldValue, $valueType) ?? throw new CannotResolveValueException(\sprintf('Cannot resolve value for path %s', \implode('.', $path)));
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
