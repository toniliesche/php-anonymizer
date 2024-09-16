<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess\Provider;

use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\AutoDetectDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\DataAccess\PropertyDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\ReflectionDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\SetterDataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataAccessExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataAccessException;
use function in_array;
use function sprintf;

class DefaultDataAccessProvider implements DataAccessProviderInterface
{
    private const DATA_ACCESSES = [
        DataAccess::ARRAY->value,
        DataAccess::AUTODETECT->value,
        DataAccess::PROPERTY->value,
        DataAccess::REFLECTION->value,
        DataAccess::SETTER->value,
    ];

    /** @var array<string, DataAccessInterface> */
    private array $dataAccesses = [];

    /** @var string[] */
    private array $customDataAccesses = [];

    public function registerCustomDataAccess(string $name, DataAccessInterface $dataAccess): void
    {
        if ($this->supports($name) || in_array($name, [DataAccess::DEFAULT->value, DataAccess::AUTODETECT->value], true)) {
            throw new DataAccessExistsException(sprintf('Cannot override existing data access: "%s"', $name));
        }

        $this->customDataAccesses[] = $name;
        $this->dataAccesses[$name] = $dataAccess;
    }

    public function supports(string $dataAccess): bool
    {
        return in_array($dataAccess, self::DATA_ACCESSES, true) || in_array($dataAccess, $this->customDataAccesses, true);
    }

    public function provideDataAccess(string $dataAccess): DataAccessInterface
    {
        if (!isset($this->dataAccesses[$dataAccess])) {
            $this->dataAccesses[$dataAccess] = $this->resolveDataAccess($dataAccess);
        }

        return $this->dataAccesses[$dataAccess];
    }

    private function resolveDataAccess(string $dataAccess): DataAccessInterface
    {
        return match ($dataAccess) {
            DataAccess::AUTODETECT->value => new AutoDetectDataAccess(
                [
                    new ArrayDataAccess(),
                    new PropertyDataAccess(),
                    new SetterDataAccess(),
                ],
            ),
            DataAccess::ARRAY->value => new ArrayDataAccess(),
            DataAccess::PROPERTY->value => new PropertyDataAccess(),
            DataAccess::REFLECTION->value => new ReflectionDataAccess(),
            DataAccess::SETTER->value => new SetterDataAccess(),
            default => throw new UnknownDataAccessException(sprintf('Unknown data access: "%s"', $dataAccess)),
        };
    }
}
