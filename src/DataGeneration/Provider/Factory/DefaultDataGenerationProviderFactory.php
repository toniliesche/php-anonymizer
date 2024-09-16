<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory;

use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Exception\DataGenerationProviderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataGenerationProviderDefinitionException;

class DefaultDataGenerationProviderFactory implements DataGenerationProviderFactoryInterface
{
    private const DATA_GENERATION_PROVIDERS = [
        DataGenerationProvider::DEFAULT->value,
    ];

    /** @var array<string,DataGenerationProviderInterface> */
    private array $dataGenerationProviders = [];

    /** @var array<string,callable> */
    private array $customDataGenerationProviders = [];

    /**
     * @param callable|DataGenerationProviderInterface $definition
     */
    public function registerCustomDataGenerationProvider(string $name, mixed $definition): void
    {
        if (\in_array($name, self::DATA_GENERATION_PROVIDERS, true) || \in_array($name, $this->customDataGenerationProviders, true)) {
            throw new DataGenerationProviderExistsException(\sprintf('Cannot override existing data generation provider: "%s"', $name));
        }

        if ($definition instanceof DataGenerationProviderInterface) {
            $this->customDataGenerationProviders[$name] = static fn () => $definition;

            return;
        }

        if (!\is_callable($definition)) {
            throw new InvalidDataGenerationProviderDefinitionException('Data generation provider definition must be a callable');
        }

        $this->customDataGenerationProviders[$name] = $definition;
    }

    public function getDataGenerationProvider(?string $type): ?DataGenerationProviderInterface
    {
        if ($type === null) {
            return null;
        }

        if (!isset($this->dataGenerationProviders[$type])) {
            $this->dataGenerationProviders[$type] = $this->resolveDataGenerationProvider($type);
        }

        return $this->dataGenerationProviders[$type];
    }

    private function resolveDataGenerationProvider(string $type): DataGenerationProviderInterface
    {
        if (isset($this->customDataGenerationProviders[$type])) {
            $dataGenerationProvider = $this->customDataGenerationProviders[$type]();
            if (!$dataGenerationProvider instanceof DataGenerationProviderInterface) {
                throw new InvalidDataGenerationProviderDefinitionException('Data generation provider must implement DataGenerationProviderInterface');
            }

            return $dataGenerationProvider;
        }

        return match ($type) {
            DataGenerationProvider::DEFAULT->value => new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            default => throw new InvalidArgumentException('Unknown data generation provider type'),
        };
    }
}
